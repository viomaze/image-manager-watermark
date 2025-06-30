<?php
/*
Plugin Name: 图片管理（上传+批量导入+水印+自动删除+重命名+ALT+Title）
Description: 支持图片上传和批量导入到媒体库，递归子目录、加水印（右上角）、重命名、ALT/Title自动写入、导入后删除原图，并防止误删水印图片。
Version: 1.4
Author: viomaze
*/

if (!defined('ABSPATH')) exit;

// 1. 上传/扫描目录（请确保存在并有写权限）
function imgmgr_get_target_dir() {
    return '/home/html/xxx.com/public_html/tools/img/'; // 记得最后加斜杠
}

// 2. 水印图片路径（需为png，建议透明，放同目录下）
function imgmgr_get_watermark_path() {
    return imgmgr_get_target_dir() . 'watermark.png';
}

// 3. 后台菜单
add_action('admin_menu', function(){
    add_menu_page('图片管理', '图片管理', 'manage_options', 'image-manager', 'imgmgr_page');
});

// 4. 主页面
function imgmgr_page(){
    $active_tab = $_GET['tab'] ?? 'upload';
    echo '<div class="wrap" style="max-width:700px">';
    echo '<h1>图片管理（上传 + 批量导入 + 水印）</h1>';
    echo '<h2 class="nav-tab-wrapper">';
    echo '<a class="nav-tab '.($active_tab=='upload'?'nav-tab-active':'').'" href="?page=image-manager&tab=upload">上传图片</a>';
    echo '<a class="nav-tab '.($active_tab=='import'?'nav-tab-active':'').'" href="?page=image-manager&tab=import">批量导入媒体库</a>';
    echo '</h2>';
    if ($active_tab == 'import') {
        imgmgr_import_tab();
    } else {
        imgmgr_upload_tab();
    }
    echo '</div>';
}

// 5. 上传Tab
function imgmgr_upload_tab(){
    $upload_dir = imgmgr_get_target_dir();
    $watermark_img = imgmgr_get_watermark_path();
    $msg = '';
    $user_provided_name = '';
    if (isset($_POST['imgmgr_upload'])) {
        // 1. 获取并清理自定义文件名
        $user_provided_name = trim($_POST['file_names'] ?? '');
        if (class_exists('Normalizer')) {
            $clean_name = Normalizer::normalize($user_provided_name, Normalizer::FORM_D);
            $clean_name = preg_replace('/\p{Mn}/u', '', $clean_name);
            $clean_name = preg_replace('/[^A-Za-z0-9\-]/', '-', $clean_name);
            $clean_name = preg_replace('/-+/', '-', $clean_name);
            $clean_name = trim($clean_name, '-');
        } else {
            $clean_name = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $user_provided_name);
            $clean_name = preg_replace('/[^A-Za-z0-9\-]/', '-', $clean_name);
            $clean_name = preg_replace('/-+/', '-', $clean_name);
            $clean_name = trim($clean_name, '-');
        }

        if (!$clean_name) $clean_name = 'image';

        if (!is_dir($upload_dir) && !mkdir($upload_dir, 0755, true)) {
            $msg = '<span style="color:#d33;">上传目录不存在且创建失败！</span>';
        } else if (!file_exists($watermark_img)) {
            $msg = '<span style="color:#d33;">未找到水印文件：'.esc_html($watermark_img).'</span>';
        } else {
            $files = $_FILES['imgmgr_files'] ?? [];
            if (!empty($files['name'][0])) {
                require_once(ABSPATH . 'wp-admin/includes/file.php');
                require_once(ABSPATH . 'wp-admin/includes/media.php');
                require_once(ABSPATH . 'wp-admin/includes/image.php');
                foreach ($files['tmp_name'] as $i => $tmp) {
                    $name = basename($files['name'][$i]);
                    $ext = strtolower(pathinfo($name, PATHINFO_EXTENSION));
                    if (!in_array($ext, ['jpg','jpeg','png','webp'])) {
                        $msg .= '<span style="color:#d33;">不支持的文件类型：'.esc_html($name).'</span><br>';
                        continue;
                    }
                    $tmp_with_wm = $upload_dir . 'tmpwm_' . uniqid() . '.' . $ext;
                    if (imgmgr_add_watermark($tmp, $watermark_img, $tmp_with_wm)) {
                        // 用 clean_name 作为前缀
                        $final_name = $clean_name . '-' . date('Ymd-His') . '-' . uniqid() . '.' . $ext;
                        $target = rtrim($upload_dir, '/').'/'.$final_name;
                        if (@rename($tmp_with_wm, $target)) {
                            // 自动导入媒体库
                            $file_array = [
                                'name'     => $final_name,
                                'type'     => mime_content_type($target),
                                'tmp_name' => $target,
                                'error'    => 0,
                                'size'     => filesize($target),
                            ];
                            $attachment_id = media_handle_sideload($file_array, 0);
                            if (is_wp_error($attachment_id)) {
                                $msg .= '<span style="color:#d33;">导入媒体库失败：</span>'.esc_html($final_name).'，'.$attachment_id->get_error_message().'<br>';
                            } else {
                                // 设置alt和title
                                wp_update_post([
                                    'ID' => $attachment_id,
                                    'post_title' => $user_provided_name,
                                ]);
                                update_post_meta($attachment_id, '_wp_attachment_image_alt', $user_provided_name);
                                $msg .= '<span style="color:#090;">上传+加水印+导入媒体库成功：</span>'.esc_html($final_name).' <br>';
                            }
                            @unlink($target);
                        } else {
                            $msg .= '<span style="color:#d33;">加水印后移动失败：</span>'.esc_html($name).'<br>';
                        }
                    } else {
                        $msg .= '<span style="color:#d33;">加水印失败：</span>'.esc_html($name).'<br>';
                    }
                }
            } else {
                $msg = '<span style="color:#d33;">请先选择图片！</span>';
            }
        }
    }
    ?>
    <h3>批量上传图片到指定目录（自动加水印、重命名、自动导入媒体库）</h3>
    <form method="post" enctype="multipart/form-data">
        <p>
            <label>图片标题/ALT/文件名：<input type="text" name="file_names" value="<?php echo esc_attr($user_provided_name); ?>" required style="width:240px;"></label>
        </p>
        <p>
            <input type="file" name="imgmgr_files[]" multiple accept="image/*" required>
        </p>
        <button class="button button-primary" type="submit" name="imgmgr_upload" value="1">上传</button>
    </form>
    <p>上传目录：<code><?php echo esc_html($upload_dir); ?></code></p>
    <p>水印图片：<code><?php echo esc_html($watermark_img); ?></code></p>
    <div><?php echo $msg; ?></div>
    <?php
}

// 6. 递归获取所有图片文件，自动排除watermark.png
function imgmgr_get_all_images($dir) {
    $rii = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($dir));
    $files = [];
    foreach ($rii as $file) {
        if ($file->isDir()) continue;
        if (basename($file) == 'watermark.png') continue; // 忽略水印图片本身
        $ext = strtolower($file->getExtension());
        if (in_array($ext, ['jpg','jpeg','png','webp'])) {
            $files[] = $file->getPathname();
        }
    }
    return $files;
}

// 7. 批量导入Tab
function imgmgr_import_tab(){
    $import_dir = imgmgr_get_target_dir();
    $watermark_img = imgmgr_get_watermark_path();
    $msg = '';
    $user_provided_name = trim($_POST['file_names'] ?? '');
    $clean_name = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $user_provided_name);
    $clean_name = preg_replace('/[^A-Za-z0-9\-]/', '-', $clean_name);
    $clean_name = preg_replace('/-+/', '-', $clean_name);
    $clean_name = trim($clean_name, '-');
    if (!$clean_name) $clean_name = 'image';
    if (isset($_POST['imgmgr_import'])) {
        if (!is_dir($import_dir)) {
            $msg = "指定目录不存在：<b>$import_dir</b>";
        } else if (!file_exists($watermark_img)) {
            $msg = '未找到水印文件：'.esc_html($watermark_img);
        } else {
            $files = imgmgr_get_all_images($import_dir);
            if (empty($files)) {
                $msg = '没有发现图片文件！';
            } else {
                require_once(ABSPATH . 'wp-admin/includes/file.php');
                require_once(ABSPATH . 'wp-admin/includes/media.php');
                require_once(ABSPATH . 'wp-admin/includes/image.php');
                foreach ($files as $file) {
                    $info = getimagesize($file);
                    if (!$info || !in_array($info[2], [IMAGETYPE_JPEG, IMAGETYPE_PNG, IMAGETYPE_WEBP])) {
                        $msg .= '<span style="color:#d33;">不支持的图片格式：</span>'.esc_html(basename($file)).'<br>';
                        continue;
                    }
                    $ext = strtolower(pathinfo($file, PATHINFO_EXTENSION));
                    $tmp_with_wm = sys_get_temp_dir() . '/imgmgr_wm_' . uniqid() . '.' . $ext;
                    if (imgmgr_add_watermark($file, $watermark_img, $tmp_with_wm)) {
                        $final_name = $clean_name . '-' . uniqid() . '.' . $ext;
                        $file_array = [
                            'name'     => $final_name,
                            'type'     => mime_content_type($tmp_with_wm),
                            'tmp_name' => $tmp_with_wm,
                            'error'    => 0,
                            'size'     => filesize($tmp_with_wm),
                        ];
                        $attachment_id = media_handle_sideload($file_array, 0);
                        @unlink($tmp_with_wm);
                        if (is_wp_error($attachment_id)) {
                            $msg .= '<span style="color:#d33;">失败</span>：' . esc_html(basename($file)) . '，' . esc_html($attachment_id->get_error_message()) . '<br>';
                        } else {
                            wp_update_post([
                                'ID' => $attachment_id,
                                'post_title' => $user_provided_name,
                            ]);
                            update_post_meta($attachment_id, '_wp_attachment_image_alt', $user_provided_name);
                            if (@unlink($file)) {
                                $msg .= '<span style="color:#090;">成功</span>：' . esc_html($final_name) . '，ID：' . esc_html($attachment_id) . ' <span style="color:#666;">（已删除原文件）</span><br>';
                            } else {
                                $msg .= '<span style="color:#090;">成功</span>：' . esc_html($final_name) . '，ID：' . esc_html($attachment_id) . ' <span style="color:#d33;">（删除原文件失败）</span><br>';
                            }
                        }
                    } else {
                        $msg .= '<span style="color:#d33;">加水印失败：</span>'.esc_html(basename($file)).'<br>';
                    }
                    @set_time_limit(30);
                    @ob_flush(); @flush();
                }
            }
        }
    }
    ?>
    <h3>批量导入图片到媒体库（加水印、重命名、自动ALT/TITLE、删除原文件）</h3>
    <form method="post">
        <p>
            <label>图片标题/ALT/文件名：<input type="text" name="file_names" value="<?php echo esc_attr($user_provided_name); ?>" required style="width:240px;"></label>
        </p>
        <button class="button button-primary" type="submit" name="imgmgr_import" value="1">开始批量导入</button>
    </form>
    <p>扫描目录：<code><?php echo esc_html($import_dir); ?></code></p>
    <p>水印图片：<code><?php echo esc_html($watermark_img); ?></code></p>
    <div style="max-height:300px;overflow:auto;"><?php echo $msg; ?></div>
    <hr>
    <small>温馨提示：<br>
        1. <b>水印图片需放到指定目录，名为 <code>watermark.png</code></b><br>
        2. 文件夹必须有服务器读写权限。<br>
        3. 图片过多时建议分批。<br>
        4. 如需更改目录，请编辑插件中的 <code>imgmgr_get_target_dir()</code>。<br>
    </small>
    <?php
}

// 8. 水印叠加（右上角，支持透明PNG水印，自动识别格式）
function imgmgr_add_watermark($imagePath, $watermarkPath, $targetPath) {
    // 检测图片类型
    $info = getimagesize($imagePath);
    if (!$info) return false;
    switch ($info[2]) {
        case IMAGETYPE_JPEG:
            $image = imagecreatefromjpeg($imagePath); break;
        case IMAGETYPE_PNG:
            $image = imagecreatefrompng($imagePath); break;
        case IMAGETYPE_WEBP:
            $image = imagecreatefromwebp($imagePath); break;
        default: return false;
    }
    $watermark = imagecreatefrompng($watermarkPath); //水印只支持png

    if (!$image || !$watermark) return false;
    imagealphablending($image, true);
    imagesavealpha($image, true);

    $imgW = imagesx($image);
    $imgH = imagesy($image);
    $wmW = imagesx($watermark);
    $wmH = imagesy($watermark);

    // 右上角（离边距10像素）
    $x = $imgW - $wmW - 10;
    $y = 10;

    // 防止水印超过边界
    if ($x < 0) $x = 0;
    if ($y < 0) $y = 0;

    imagecopy($image, $watermark, $x, $y, 0, 0, $wmW, $wmH);

    // 按源图格式保存
    $result = false;
    switch ($info[2]) {
        case IMAGETYPE_JPEG: $result = imagejpeg($image, $targetPath, 90); break;
        case IMAGETYPE_PNG:  $result = imagepng($image, $targetPath, 6); break;
        case IMAGETYPE_WEBP: $result = imagewebp($image, $targetPath, 90); break;
    }

    imagedestroy($image);
    imagedestroy($watermark);

    return $result;
}

