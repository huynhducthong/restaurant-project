<?php
$zip = new ZipArchive;
if ($zip->open('c:\xampp\htdocs\restaurant-project\baocaoduantotnghiep.docx') === TRUE) {
    $content = $zip->getFromName('word/document.xml');
    $zip->close();
    $text = strip_tags(str_replace(['<w:p>', '<w:p '], "\n<w:p>", $content));
    echo mb_substr(trim($text), 0, 1000);
} else {
    echo 'Failed to open';
}
