#!/bin/bash
#
# SIRUM RESTORE SCRIPT BY ADAM KIRCHER
#
# Create a new server of user defined size based on the current
# server (increase/decrease scale) or an historic backup (restore).
# Debug errors with /var/log/syslog or by using set -x -e
#
# Make this program double-clickable by running chmod +x on it and
# changing the program to always open in terminal (not text edit)
#
# TEN STEP PROCESS:
# 1) Verify User Identity and Get Key Info
# 2) Create a Snapshot of the Current Server
# 3) Let User Pick Which Snapshot to Restore
# 4) Register Snapshot as an Temp AMI Image
# 5) Create Instance with Blockdevice using AMI 
# 6) Unregister the Temporary AMI Image
# 7) Once running, SSH into the New Instance 
# 8) Encrypt the Blockdevice for Credentials
# 9) Create Key File and Upload the Keys
# 10) Start Apache, Mysql, and Wrap Up
#
# CONSTANTS
KEY_NAMES=(AWS_ACCESS_KEY_ID AWS_SECRET_KEY ENCRYPT MYSQL CRON)
LOCAL_KEY_PATH="/Volumes/EC2"
REMOTE_KEY_PATH="/var/www/sirum/keys"

REMOTE_ENV_FILE="/var/www/sirum/html/index.php"
LOCAL_DEBUG_FILE="Desktop/debug_sirum_restore.txt"

BLOCK_DEVICE_SIZE="1"
NUM_BACKUPS=20
#
# Notes
# AWS REST API HELPER 
# Creates Signed REST Query Requests to Amazon's EC2. List of actions and parameters at 
# http://docs.amazonwebservices.com/AWSEC2/latest/APIReference/OperationList-query.html
# Based on docs.amazonwebservices.com/AWSEC2/latest/UserGuide/using-query-api.html
# And Dan Myer's Amazon EC2 PHP Class at http://sourceforge.net/projects/php-ec2/

function REST_API
{
	END="https://ec2.us-west-1.amazonaws.com/?AWSAccessKeyId=${KEY_VALUES[0]}&Action=$1"
	GET="GET\nec2.us-west-1.amazonaws.com\n/\nAWSAccessKeyId=${KEY_VALUES[0]}&Action=$1"
        
        PARAMS=$2
        AUTH=("Version=2012-06-01" "SignatureMethod=HmacSHA1" "SignatureVersion=2" "Timestamp=$(date '+%Y-%m-%dT23:59:59%Z')")
        
        #create array with all parameters
        URL_ARRAY=(${PARAMS[@]} ${AUTH[@]})
                                  
	#poorly documented but trick here is to sort all params into alphabetical order
	URL_ARRAY=($(printf '%s\0' "${URL_ARRAY[@]}" | sort -z | xargs -0n1)) 
       
        #urlencode and implode with & 
        for i in ${!URL_ARRAY[*]}; do
                URL="$URL&"$( echo ${URL_ARRAY[$i]} | tr -d '\n' | perl -p -e 's/([^A-Za-z0-9\-\.=_])/sprintf("%%%02X", ord($1))/seg')
        done
        
	echo $(curl -s "$END$URL&Signature="$(echo -en "$GET$URL" | openssl dgst -sha1 -hmac "${KEY_VALUES[1]}" -binary | openssl enc -base64 | tr -d '\n' | perl -p -e 's/([^A-Za-z0-9\-\.=_])/sprintf("%%%02X", ord($1))/seg'))
}

function PARSE_TAG()
{
    while read data; do
      input=( "${input[@]}" "$data" );
    done
    
    if $(echo $input | grep -q "<Error>"); then
       echo "$(date)------
       PARAMS: ${PARAMS[@]}
       RESPONSE: $input
       " >> $LOCAL_DEBUG_FILE
    fi
    
    echo $(echo $input | grep -o "<$1>[^<]*</$1>" | grep -o ">[^<]*" | cut -c 2-)
}

#----------------------------- NOW THIS SCRIPT TAKES OVER!!! ----------------------------------
# 1) Verify User Identity and Get Key Info 

echo "
SIRUM RESTORE SCRIPT BY ADAM KIRCHER
This is a powerful program. If unsure, quit and ask for help.
Use ^c (control c) to quit program.

Please verify your identity by entering the following credentials
"
#didn't make key_values associative since wanted compatability with bash 3
for (( i=0;i<${#KEY_NAMES[@]};i++)); do
    read -e -p "Enter ${KEY_NAMES[$i]}: " KEY_VALUES[$i]
done

#-----------------------------------------------------------------------------------------------------------------
# 2) Get a list of all running instances

echo "
Getting list of running servers now...
"
PARAMS=("Filter.1.Name=instance-state-name" "Filter.1.Value.1=running")
INSTANCES=$(REST_API "DescribeInstances" $PARAMS)
#echo ${INSTANCES[@]} #DEBUGGING

#Store much about the instances as possible to avoid extra API call later
TAG_VALUES=($(echo $INSTANCES | PARSE_TAG "value"))
INSTANCE_IDS=($(echo $INSTANCES | PARSE_TAG "instanceId"))
IP_ADDRESSES=($(echo $INSTANCES | PARSE_TAG "ipAddress"))
VOLUME_IDS=($(echo $INSTANCES | PARSE_TAG "volumeId")) 
DEVICE_NAMES=($(echo $INSTANCES | PARSE_TAG "deviceName"))
INSTANCE_TYPES=($(echo $INSTANCES | PARSE_TAG "instanceType"))
GROUP_IDS=($(echo $INSTANCES | PARSE_TAG "groupId"))
AVAIL_ZONES=($(echo $INSTANCES | PARSE_TAG "availabilityZone"))
SSH_KEY_FILES=($(echo $INSTANCES | PARSE_TAG "keyName"))
KERNEL_IDS=($(echo $INSTANCES | PARSE_TAG "kernelId"))

#-----------------------------------------------------------------------------------------------------------------
# 3) Ask User Which Server to Restore

echo "Servers:"

# all attributes are one per device except for volume and devices
# we need to get these two attributes' indexes to line up with the
# servers' indexes so when user chooses server we know the root volume
for (( i=0; i<${#IP_ADDRESSES[@]}; i++)); do
    
    #skip non-root volumes
    for (( j=i; j<${#VOLUME_IDS[@]}; j++)); do
    
        #swap the root volume to the right index
        if [[ ${DEVICE_NAMES[$j]} == '/dev/sda1' ]]; then
            
            #splice it in
            VOLUME_IDS=(${VOLUME_IDS[@]:0:$i} ${VOLUME_IDS[$j]} ${VOLUME_IDS[@]:$i:$(($j-$i))} ${VOLUME_IDS[@]:$(($j+1))})
            DEVICE_NAMES=(${DEVICE_NAMES[@]:0:$i} ${DEVICE_NAMES[$j]} ${DEVICE_NAMES[@]:$i:$(($j-$i))} ${DEVICE_NAMES[@]:$(($j+1))})
            break
        fi
    done
     
    echo "$i) ${TAG_VALUES[$i]} ${IP_ADDRESSES[$i]} - ${INSTANCE_TYPES[$i]}" 
done

read -p "
Select the number of the server you wish to restore: " SERVER_INDEX

#-----------------------------------------------------------------------------------------------------------------
# 4) Ask user what speed and environment they want

INSTANCE_TYPES=("t1.micro" "m1.small" "m1.medium" "m1.large" "m1.xlarge")

echo "
Available Speeds:"

for (( i=0;i<${#INSTANCE_TYPES[@]};i++)); do
    echo "$i) ${INSTANCE_TYPES[$i]}" 
done

read -p "
Select the number of the speed you want: " TYPE_INDEX

#-----------------------------------------------------------------------------------------------------------------
# 5) List 20 most recent backupsLet (hack: using rev to sort in descending cron order)

echo "
Retrieving the $NUM_BACKUPS most recent backups of ${TAG_VALUES[$SERVER_INDEX]}...
"
PARAMS=("Filter.1.Name=volume-id" "Filter.1.Value.1=${VOLUME_IDS[$SERVER_INDEX]}")
SNAPSHOTS=$(REST_API "DescribeSnapshots" $PARAMS | rev)

PARAMS=("VolumeId=${VOLUME_IDS[$SERVER_INDEX]}")
LIVE_SNAPSHOT=$(REST_API "CreateSnapshot" $PARAMS)

SNAPSHOT_IDS=($(echo $LIVE_SNAPSHOT | PARSE_TAG "snapshotId"))
START_TIMES=($(date -u '+%Y-%m-%dT%T'))

SNAPSHOT_IDS+=($(echo $SNAPSHOTS | grep -o ">dItohspans/<[^>]*>dItohspans<" | rev | cut -c 13-25 | head -n ${NUM_BACKUPS-1}))
START_TIMES+=($(echo $SNAPSHOTS | grep -o ">emiTtrats/<[^>]*>emiTtrats<" | rev | cut -c 12-30 | head -n ${NUM_BACKUPS-1}))

VOLUME_SIZE=$(echo $LIVE_SNAPSHOT | PARSE_TAG "volumeSize")

#-----------------------------------------------------------------------------------------------------------------
# 6) User Picks Which Snapshot to Restore

echo "Backups of ${TAG_VALUES[$SERVER_INDEX]} (times given in $(date +%Z)):"

for (( i=0;i<${#SNAPSHOT_IDS[@]};i++)); do
    echo "$i) Created at $(date -jf  %Y-%m-%dT%T%Z ${START_TIMES[$i]}GMT '+%T on %Y-%m-%d')" 
done

read -p "
Enter the number of the backup to use: " SNAPSHOT_INDEX

BACKUP_DATE=$(date -jf  %Y-%m-%dT%T%Z ${START_TIMES[${SNAPSHOT_INDEX}]}GMT '+%Y-%m-%d-%H%M')

#-----------------------------------------------------------------------------------------------------------------
# 7) Register Snapshot as a Temp AMI Image

echo "
Restoring $BACKUP_DATE backup. This can take several minutes:"

PARAMS=("SnapshotId=${SNAPSHOT_IDS[$SNAPSHOT_INDEX]}")

while ! $(REST_API "DescribeSnapshots" $PARAMS | grep -q 'completed')
do 
    echo -n ">"
    sleep 3
done

PARAMS=("Name=$(date '+%Y-%m-%d-%H%M')_$BACKUP_DATE" "RootDeviceName=/dev/sda1" "BlockDeviceMapping.1.DeviceName=/dev/sda1" "BlockDeviceMapping.1.Ebs.SnapshotId=${SNAPSHOT_IDS[$SNAPSHOT_INDEX]}")

IMAGE_ID=$(REST_API "RegisterImage" $PARAMS | PARSE_TAG "imageId")

#-----------------------------------------------------------------------------------------------------------------
# 8) Create Instance with Blockdevice using AMI

echo "
Waiting for server to boot. This can take several minutes:"

#Not well documented but need to supply kernel id https://forums.aws.amazon.com/thread.jspa?threadID=94995 ${DEVICE_NAMES[$SERVER_INDEX]}
PARAMS=("ImageId=$IMAGE_ID" "KernelId=${KERNEL_IDS[$SERVER_INDEX]}" "MinCount=1" "MaxCount=1" "KeyName=${SSH_KEY_FILES[$SERVER_INDEX]}" "SecurityGroupId.1=${GROUP_IDS[$SERVER_INDEX]}" "InstanceType=${INSTANCE_TYPES[$TYPE_INDEX]}" "Placement.AvailabilityZone=${AVAIL_ZONES[$SERVER_INDEX]}" "BlockDeviceMapping.1.DeviceName=/dev/sda1" "BlockDeviceMapping.2.DeviceName=${DEVICE_NAMES[$(($SERVER_INDEX+${#IP_ADDRESSES[@]}))]}" "BlockDeviceMapping.1.Ebs.VolumeSize=$VOLUME_SIZE" "BlockDeviceMapping.2.Ebs.VolumeSize=$BLOCK_DEVICE_SIZE" "BlockDeviceMapping.1.Ebs.DeleteOnTermination=false" "BlockDeviceMapping.2.Ebs.DeleteOnTermination=false")
INSTANCE_ID=$(REST_API "RunInstances" $PARAMS | PARSE_TAG "instanceId")

#RunInstances doesn't take Name as a parameter
PARAMS=("ResourceId.1=$INSTANCE_ID" "Tag.1.Key=Name" "Tag.1.Value=Backup_$BACKUP_DATE")
RETURN=$(REST_API "CreateTags" $PARAMS | PARSE_TAG "return")

#-----------------------------------------------------------------------------------------------------------------
# 9) Get IP address and clean up files

#not well documented but ipAddress not assigned immediately so runInstance doesn't return
PARAMS=("InstanceId=$INSTANCE_ID")

while ! $(REST_API "DescribeInstances" $PARAMS | grep -q "running")
do
    echo -n ">"
    sleep 3
done

INSTANCE=$(REST_API "DescribeInstances" $PARAMS)
IP_ADDRESS=$(echo $INSTANCE | PARSE_TAG "ipAddress")
NEW_VOL_IDS=($(echo $INSTANCE | PARSE_TAG "volumeId"))

PARAMS=("ImageId=$IMAGE_ID")
RETURN=$(REST_API "DeregisterImage" $PARAMS)

#-----------------------------------------------------------------------------------------------------------------
# 10) Wait for the new server to boot

echo "
Logging into $IP_ADDRESS. This can take several minutes:"

# simply checking describeInstances with running doesn't
# guarantee that port 22 is open yet. we need to check
while ! $(nc -z -w 2 $IP_ADDRESS 22 | grep -q "succeeded")
do
    echo -n ">"
    sleep 3
done

#-----------------------------------------------------------------------------------------------------------------
# 11) SSH into the server and encrypt the block device

echo "
We are now logged into $IP_ADDRESS
"
ssh -t -t -o "UserKnownHostsFile=/dev/null" -o "StrictHostKeyChecking=no" -i "$LOCAL_KEY_PATH/${SSH_KEY_FILES[$SERVER_INDEX]}.pem" "ec2-user@$IP_ADDRESS" <<EOF

ENV=\$(cat $REMOTE_ENV_FILE | grep -o -m 1 "'[^']*')" | grep -o "[a-zA-Z]*")

KEY_NAMES=(${KEY_NAMES[@]})

KEY_VALUES=(${KEY_VALUES[@]})

sudo echo $(echo ${KEY_VALUES[@]} | rev) >> temp.txt

sudo cryptsetup -q -c aes-cbc-essiv:sha256 --key-size 256 luksFormat /dev/sdf ~/temp.txt

sudo cryptsetup luksOpen /dev/sdf keys --key-file ~/temp.txt

sudo shred -u -z -n 26 ~/temp.txt

sudo mkfs.ext3 /dev/mapper/keys

sudo mkdir -p $REMOTE_KEY_PATH

sudo mount /dev/mapper/keys $REMOTE_KEY_PATH

sudo chmod -R 777 $REMOTE_KEY_PATH

sudo cp $REMOTE_KEY_PATH-/common.php $REMOTE_KEY_PATH/common.php

sudo echo "<?php  if ( ! defined('BASEPATH') && ! defined('EXT')) exit('No direct script access allowed');
/*
* This is key file was automatically generated by aws_startup.sh by Adam Kircher
*/

include('common.php');

define('AWS_VOLUME', '${NEW_VOL_IDS[0]}');
" >> "$REMOTE_KEY_PATH/\$ENV.php"

for (( i=0;i<${#KEY_NAMES[@]};i++)); do
    sudo echo "
    define('\${KEY_NAMES[\$i]}', '\${KEY_VALUES[\$i]}');"  >> "$REMOTE_KEY_PATH/\$ENV.php" 
done

sudo chown -R apache $REMOTE_KEY_PATH

sudo chmod -R 500 $REMOTE_KEY_PATH

sudo service httpd start

sudo service mysqld start

exit

EOF

#-----------------------------------------------------------------------------------------------------------------
# 12) Redirect traffic and stop server if user wants to

echo "
GOTO $IP_ADDRESS AND TEST THE NEW SERVER!
"

read -r -p "Type confirm to redirect traffic to new server (anything else will quit): " response

if [[ $response =~ ^([Cc][Oo][Nn][Ff][Ii][Rr][Mm])$ ]]
then

IP_ADDRESS="${IP_ADDRESSES[$SERVER_INDEX]}"

#When reassigned, automatically dissaciates with old instance AllowReassociation
PARAMS=("PublicIp=$IP_ADDRESS" "InstanceId=$INSTANCE_ID")
RETURN=$(REST_API "AssociateAddress" $PARAMS | PARSE_TAG "return")

#When reassigned, automatically dissaciates with old instance
PARAMS=("InstanceId=${INSTANCE_IDS[$SERVER_INDEX]}")
RETURN=$(REST_API "StopInstances" $PARAMS | PARSE_TAG "currentState")

echo "
Traffic was redirected
"

else

echo "
Traffic was not redirected
"

fi

echo "RESTORATION IS COMPLETE.

LOGIN: ssh -i $LOCAL_KEY_PATH/${SSH_KEY_FILES[$SERVER_INDEX]}.pem ec2-user@$IP_ADDRESS
"

#END OF SIRUMRESTORE