# Production
- Update `sudo git pull` (or create it `sudo git clone https://github.com/dscsa/v1`)
- Create symbolic link from repo to `/var/www/sirum`
- Add `/key` folder.  Create based on `key-` or use symbolic link
- Add write permissions `sudo chmod a+w` to
  - `/url/manifest`
  - `/url/label`
  - `/url/import_errors.csv`
- Update `/url/index.php` with the correct environment
