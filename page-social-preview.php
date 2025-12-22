<?php
/* Template Name: Social Preview */
$post_id = intval($_GET["social_preview_id"] ?? 0);

// unserialize stored data
$data = maybe_unserialize(get_post_meta($post_id, "social_data", true));
$logo = get_post_meta($post_id, "logo_url", true);
$bodyImage = get_post_meta($post_id, "body_image_url", true);

if (!$data) {
    echo "<p>Invalid preview.</p>";
    exit;
}
?>
<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
    <meta charset="<?php bloginfo('charset'); ?>" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Social Preview</title>
    <style>
    /* Place your CSS here (existing .social-preview-card styles) */
    *{box-sizing:border-box;margin:0;padding:0;}
    .social-preview-card {
        border: 5px solid #0067b4;
        height: 550px;
        overflow-y: auto;
        width: calc(100% - 40px);
        margin: 40px auto;
        border-radius: 24px;
        padding: 20px;
        max-width: 330px;
        font-family: "poppins";
    }
    .social-preview-card .top-logo {max-height: 40px;margin-bottom: 10px;width: 40px;}
    .social-preview-card .social-links { display:flex; gap:20px; margin-top:20px; }  
    .social-preview-card .social-links p { margin:0; display:flex; align-items:center; gap:8px; justify-content:center; }
    .social-preview-card .social-links p a { padding:10px; border:1px solid #0067b4; border-radius:5px; display:flex;}
    .social-preview-card .social-links p a img { width:20px; height:20px; }
    .social-preview-card img.cube-image {width: 100%;height: 250px;object-fit: cover;display: flex;margin: 15px 0px;}
    .social-preview-card .header h5 {font-size: 24px;line-height: 1.2;}
    .social-preview-card .content.body-content-wrapper {padding: 10px;}
    .social-preview-card .content.body-content-wrapper h5 {font-size: 20px;line-height: 1.2;}
    .social-preview-card .content.body-content-wrapper p {font-size: 18px;line-height: 1.2;}
    .footer.social-links {justify-content: center;}

    .social-preview-card::-webkit-scrollbar-track 
{
	-webkit-box-shadow: inset 0 0 6px rgba(0,0,0,0.3);
	background-color: #F5F5F5;
  border-radius: 10px;
  margin-left: 10px;
}

.social-preview-card::-webkit-scrollbar 
{
	width: 10px;
	background-color: #F5F5F5;
  border-radius: 10px;
  margin-left: 10px;
}

.social-preview-card::-webkit-scrollbar-thumb 
{
	background-color: #5c9aec;
	border-radius: 10px;
  margin-left: 10px;
	background-image: -webkit-gradient(linear, 0 0, 0 100%,
					   color-stop(.5, transparent), to(transparent));
}
    @media only screen and (max-width:767px) {
        .social-preview-card .top-logo { max-height:50px; margin-bottom:10px; width:50px; object-fit:contain; }
        .social-preview-card { border-radius:14px; }
    }
    </style>
</head>
<body>
    <div class="preview card social-preview-card">
        <div class="header">
            <?php if ($logo): ?>
                <img src="<?php echo esc_url($logo); ?>" alt="Logo" class="top-logo">
            <?php endif; ?>
            <h5 style="color:<?php echo esc_attr($data['titleColor']); ?>;"><?php echo esc_html($data['title']); ?></h5>
        </div>

        <?php if ($bodyImage): ?>
            <img class="cube-image" src="<?php echo esc_url($bodyImage); ?>" alt="Body Image" />
        <?php endif; ?>

        <div class="content body-content-wrapper"  style="background: <?php echo esc_attr($data['bgColor']); ?>">
            <h5><?php echo esc_html($data['message']); ?></h5>
            <p><?php echo esc_html($data['subtext']); ?></p>
        </div>

        <div class="footer social-links">
            <?php 
            $platform_icons = [
                "Instagram" => site_url() . "/wp-content/uploads/2025/08/Instagram.png",
                "Youtube"   => site_url() . "/wp-content/uploads/2025/08/Youtube.png",
                "Twitter"   => site_url() . "/wp-content/uploads/2025/08/Twitter.png",
                "Facebook"  => site_url() . "/wp-content/uploads/2025/08/Facebook.png",
            ];

            if (!empty($data['links'])) :
                foreach ($data['links'] as $link): 
                    $platform = $link['platform'];
                    $icon_url = $platform_icons[$platform] ?? '';
                    if ($icon_url && !empty($link['url'])):
            ?>
                <p>
                    <a href="<?php echo esc_url($link['url']); ?>" target="_blank">
                        <img src="<?php echo esc_url($icon_url); ?>" alt="<?php echo esc_attr($platform); ?>">
                    </a>
                </p>
            <?php 
                    endif;
                endforeach;
            endif;
            ?>
        </div>
    </div>
</body>
</html>
