<script>
/* A simple redirect would not break us out of an iframe. so
 * we respond with this instead, to make sure we are full-sized
 */
top.location.replace('//localhost/<?= ');
</script>