<?php 
$so_option = get_option('so_options');
$so_thread = str_replace(home_url(), '', get_permalink()); 
if ($so_option && isset($so_option['so_shortname']) && ($so_option['so_shortname']!='')){
?>
<div class="so_comments" data-sitename="<?php echo $so_option['so_shortname']; ?>" data-thread_url="<?php echo $so_thread; ?>"></div>
<script src="//api.solidopinion.com/widget/embed.js" async="async"></script>
<a href="http://api.solidopinion.com/seo/<?php echo $so_option['so_shortname']; ?><?php echo $so_thread; ?>seo.html" style="font-size:10px;"><?php echo __('Сomments аrchive');?></a>
<?php } ?>