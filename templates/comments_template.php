<?php 
$so_option = get_option('so_options');
$so_thread = urlencode($_SERVER['REQUEST_URI']);
if ($so_option && isset($so_option['so_shortname']) && ($so_option['so_shortname']!='')){
?>
<div class="so_comments" data-sitename="<?php echo $so_option['so_shortname']; ?>"></div>
<script src="//api.solidopinion.com/widget/embed.js" async="async"></script>
<noscript><a href="http://api.solidopinion.com/frontend/simple/?sitename=<?php echo $so_option['so_shortname']; ?>&thread_url=<?php echo $so_thread; ?>">Solidopinion Comments</a></noscript>
<?php } ?>