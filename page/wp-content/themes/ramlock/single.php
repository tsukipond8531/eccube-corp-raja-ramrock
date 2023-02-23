<?php get_header(); ?>

<div class="cmncontent">


	<ul class="breadcrumb cmnbox01">
		<li class="breadcrumb_list"><a href="/">HOME</a></li>
		<li class="breadcrumb_list"><a href="/page/">新着情報</a></li>
		<li class="breadcrumb_list"><?php echo the_title();?></li>
	</ul>


    <section class="single_sec">
        <div class="underblock">
            <div class="cmnbox01">
        
                <div class="single_content">
                    <h1><?php echo the_title();?></h1>
                    <?php while ( have_posts() ) : the_post(); ?>
                        <?php the_content(); ?>
                    <?php endwhile;?>
                </div>
            
    			<div class="tnews06">
    				<a href="/page/" class="cmnbtn type01">
    					<span class="cmnbtnicon"><img src="/html/template/default/assets/img/common/icon_news.svg"></span>
    					<span class="cmnbtntxt">新着情報一覧</span>
    				</a>
    			</div>
            </div>
        </div>
    </section>
</div>


<?php get_footer(); ?>