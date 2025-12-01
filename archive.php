<?php
$orgCode = $_GET['org'] ?? null;

$archiveBasePath = __DIR__ . '/archive';
$webBasePath = '/osaDashboard/archive'; // ✅ adjust path for OSA folder
$folderPath = $archiveBasePath . '/' . $orgCode;
$webOrgPath = $webBasePath . '/' . rawurlencode($orgCode);
$files = $orgCode && is_dir($folderPath) ? glob($folderPath . '/*.pdf') : [];
?>
<!DOCTYPE html>
<html>
<head>
    <title>OSA Archive - <?php echo htmlspecialchars($orgCode); ?></title>
    <style>
        body {
            font-family: 'Inter', sans-serif;
            padding: 30px;
            background-color: #f7f9fc;
        }
        .folder-card {
            background-color: #f0f4fa;
            padding: 20px;
            border-radius: 12px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
            margin-bottom: 15px;
            max-width: 500px;
            cursor: pointer;
            display: flex;
            justify-content: space-between;
            align-items: center;
            transition: background 0.3s ease;
        }
        .folder-card:hover {
            background-color: #e2e8f0;
        }
        .folder-info {
            display: flex;
            align-items: center;
            gap: 12px;
        }
        .folder-info img {
            width: 24px;
            height: 24px;
        }
        .doc-list {
            padding-left: 40px;
            margin-top: 10px;
            display: none;
        }
        .doc-list li {
            margin-bottom: 6px;
        }
        .doc-list a {
            text-decoration: none;
            color: #1d4ed8;
        }
        .doc-list a:hover {
            text-decoration: underline;
        }
        .message {
            color: red;
        }
    </style>
    <script>
        function toggleDocs(id) {
            const section = document.getElementById(id);
            section.style.display = section.style.display === 'none' ? 'block' : 'none';
        }
    </script>
</head>
<body>

<h1>📁 Archive for <?php echo htmlspecialchars($orgCode); ?></h1>

<?php if (!$orgCode): ?>
    <p class="message">Missing <code>?org=ORGCODE</code> in URL.</p>
<?php elseif (empty($files)): ?>
    <p>No archived documents found for <strong><?php echo htmlspecialchars($orgCode); ?></strong>.</p>
<?php else: ?>
    <div class="folder-card" onclick="toggleDocs('docs_<?php echo htmlspecialchars($orgCode); ?>')">
        <div class="folder-info">
            <div>
                <strong><?php echo htmlspecialchars($orgCode); ?></strong><br>
                <small>In Organization Archive</small>
            </div>
        </div>
        <span>⋮</span>
    </div>
    <ul class="doc-list" id="docs_<?php echo htmlspecialchars($orgCode); ?>">
        <?php foreach ($files as $file): ?>
            <?php
                $fileName = basename($file);
                $webPath = $webOrgPath . '/' . rawurlencode($fileName);
            ?>
            <li>📄 <a href="<?php echo $webPath; ?>" target="_blank"><?php echo htmlspecialchars($fileName); ?></a></li>
        <?php endforeach; ?>
    </ul>
<?php endif; ?>

</body>
</html>
