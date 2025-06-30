# 图片管理（上传+批量导入+水印+重命名+ALT+Title）

**作者**：viomaze  
**版本**：1.4  
**插件类型**：WordPress 后台工具

## 功能简介

- 支持后台一键上传图片到服务器指定目录，并自动加水印（右上角）
- 支持后台一键批量导入本地目录及其所有子文件夹的图片到媒体库
- 上传和导入过程会自动对图片重命名（可自定义前缀，支持中文转英文URL名）
- 自动为图片设置媒体库的 Alt Text 和 Title（取自自定义名称）
- 支持自动删除原图，避免服务器空间浪费
- 避免误删水印图片本身
- 支持 JPG / PNG / WEBP

---

## 安装与使用

### 1. 上传插件

- 上传整个 `image-manager-watermark` 文件夹到 WordPress 的 `/wp-content/plugins/` 目录下  
  或将其打包为 zip 后通过后台“上传插件”安装。

### 2. 准备水印图片

- 请在 `/home/html/uploads/` 目录下放置名为 `watermark.png` 的水印图片（建议为半透明PNG，分辨率不要过大）。

### 3. 启用插件

- WordPress 后台 > 插件 > 启用“图片管理（上传+批量导入+水印+自动删除+重命名+ALT+Title）”

### 4. 后台使用

- 后台侧边栏会出现 “图片管理” 菜单  
  进入后分为两个 TAB：
    - “上传图片”  
      选图片、输入名称，自动加水印、重命名、写入ALT/Title、入库
    - “批量导入媒体库”  
      递归扫描上传目录及子文件夹，统一加水印、重命名、写入ALT/Title、导入媒体库、并自动删除原图

---

## 参数说明

- **上传/扫描目录**：默认 `/home/html/uploads/`，可在插件代码中修改
- **水印图片**：必须为 PNG 格式，默认 `/home/html/uploads/watermark.png`
- **文件命名规则**：自动用你填写的“图片标题/ALT/文件名”做前缀（中文转英文），后缀为唯一编码和时间戳
- **ALT/Title**：自动使用你填写的“图片标题/ALT/文件名”

---

## 注意事项

- 服务器需具备目录的读写权限
- 建议图片水印宽度小于主图宽度一半
- ALT/Title请不要填 Emoji 或特殊符号，否则会被转为英文

---

## 常见问题

### Q: 水印图片没有生效怎么办？  
A: 检查水印图片是否为PNG且路径、文件名正确。

### Q: 上传/导入失败？  
A: 检查服务器目录写权限、水印图片是否存在，及 PHP GD 扩展是否开启。

### Q: ALT/title 不能写中文？  
A: 文件名会自动转为英文，但ALT/Title支持原文。

---

# Image Manager (Upload, Batch Import, Watermark, Rename, ALT & Title) [ENGLISH]

## Features

- Upload images with watermark (top right corner) to a specific server directory via WP admin
- Batch import all images from the specified directory and subdirectories into WP media library
- Images auto-renamed using your custom name (with smart English conversion)
- ALT text and Title automatically set to your custom name
- Imported images auto-delete original files
- Watermark image itself is protected from deletion
- Supports JPG/PNG/WEBP

## Installation

1. Upload `image-manager-watermark` folder to `/wp-content/plugins/` or upload as zip via WP admin
2. Place your watermark PNG as `/home/html/uploads/watermark.png`
3. Activate the plugin in WP admin > Plugins
4. Use “Image Manager” menu in WP admin for uploading/batch importing

## Customization

- Default upload/import dir: `/home/html/uploads/` (edit in plugin if needed)
- Watermark: PNG format only, placed in the same directory
- File naming: based on your custom input, non-ASCII chars converted
- ALT/title: set as your original input

## Notes

- Directory must be writable by server
- Watermark image should be smaller than your photos
- PHP GD extension must be enabled

## FAQ

**Q: Watermark not showing?**  
Check watermark image path, filename, and format.

**Q: Failed to upload/import?**  
Check server directory permissions, watermark image existence, and PHP GD support.

**Q: Cannot use Chinese or special chars in file name?**  
File name auto-converts, but ALT/title keep your input as-is.

---

> 有任何问题或需求，可以在本插件的 issues 区留言。
