
<?php get_header(); ?>
<div class="cmncontent">

	<section class="underMv information">
		<div class="cmnbox01">
			<div class="underMv_contImg sp"><img src="/html/template/default/assets/img/common/footer_logo.png" alt="みまもりCUBE" class="pc"><img src="/html/template/default/assets/img/information/pic_mv_sp.png" alt=""></div>
			<div class="underMv_cont">
				<div class="underMv_cont_box">
					<h2 class="underMv_cont_ttl">
						<span class="underMv_cont_icon"><img src="/html/template/default/assets/img/information/icon_mv.png" alt=""></span>
						<span class="underMv_cont_head">新着情報</span>
					</h2>
					<p class="underMv_cont_txt">株式会社ラムロックより、みまもりCUBEに関するお知らせや、イベント情報、展示会情報などの新着情報をご紹介します。</p>
				</div>
			</div>
		</div>
	</section>

	<ul class="breadcrumb cmnbox01">
		<li class="breadcrumb_list"><a href="/">HOME</a></li>
		<li class="breadcrumb_list">新着情報</li>
	</ul>
	<section>	
        <div class="tnewsblock">
            <div class="cmnbox01">
                <div class="tnews01">
                    <ul class="tnews02">
                    <?php
                        $args = array(
                        'paged'          => $paged,
                        'post_type' => 'post',
                        'posts_per_page' => 6,
                        ); 
                        $my_query = new WP_Query($args);
                        if ($my_query->have_posts()) :
                        while ($my_query->have_posts()) : $my_query->the_post();
                    ?>
                        <li>
                            <a href="<?php the_permalink(); ?>">
                                <span class="tnews03"><?php echo get_the_date("Y.m.d"); ?></span>
                                <span class="tnews04">
                                <?php $cat = get_the_category(); ?>
                                <?php $cat = $cat[0]; ?>
                                <?php if ($cat->cat_name == "イベント情報"):  ?>
                                <span class="iconcate" style="background-color:#f56e3d">
                                <?php elseif ($cat->cat_name == "お知らせ"):  ?>
                                <span class="iconcate" style="background-color:#a49c84">
                                <?php elseif ($cat->cat_name == "展示会情報"):  ?>
                                <span class="iconcate" style="background-color:#5ea05c">
                                <?php endif ?>
                                <?php echo get_cat_name($cat->term_id); ?>
                                </span>
                                </span>
                                <span class="tnews05"><?php echo the_title();?></span>
                            </a>
                        </li>
                    
                    <?php endwhile; ?>
                    <?php wp_reset_query();endif; ?>
                    </ul>
                    <div id="pagenavi">
                        <?php if(function_exists('wp_pagenavi')) wp_pagenavi(array('query' => $my_query));?>
                    </div>
                </div>
            </div>
        </div>
    </section>
</div>

<?php get_footer(); ?>