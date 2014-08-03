<?php
# Read file and pass content through the Markdown praser
$text = file_get_contents('Readme.md');
$html = \Markdown\Markdown::defaultTransform($text);
?>

<!DOCTYPE html>
<html>
<head>
    <title>PHP Markdown Lib - Readme</title>
</head>
<body>
<?php
# Put HTML content in the document
echo $html;
?>
</body>
</html>
