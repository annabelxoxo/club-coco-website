<?php
// Kop en voet van elke adminpagina.

function admin_head(string $titel, string $actief = '', bool $menu = true): void
{
    $links = [
        'dashboard'    => ['index.php', 'Overzicht'],
        'kaart'        => ['kaart.php', 'Kaart'],
        'uren'         => ['uren.php', 'Openingsuren'],
        'fotos'        => ['fotos.php', "Foto's"],
        'teksten'      => ['teksten.php', 'Teksten & gegevens'],
        'account'      => ['account.php', 'Account'],
    ];
    ?>
<!doctype html>
<html lang="nl">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<meta name="robots" content="noindex, nofollow">
<title><?= h($titel) ?> · club Coco admin</title>
<link rel="icon" href="../images/logo.png">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Bagel+Fat+One&family=Figtree:wght@400;500;700&display=swap">
<link rel="stylesheet" href="../assets/css/admin.css">
</head>
<body>
<?php if ($menu): global $huidigeGebruiker; ?>
<header class="topbar">
  <div class="topbar-in">
    <a class="brand" href="index.php">club <b>Coco</b> <span>admin</span></a>
    <nav>
      <?php foreach ($links as $sleutel => [$url, $label]): ?>
        <a href="<?= $url ?>"<?= $sleutel === $actief ? ' aria-current="page"' : '' ?>><?= h($label) ?></a>
      <?php endforeach; ?>
    </nav>
    <div class="who">
      <a href="../" target="_blank" rel="noopener">Bekijk site ↗</a>
      <form method="post" action="logout.php"><?= csrf_field() ?><button class="linkbtn">Uitloggen</button></form>
    </div>
  </div>
</header>
<?php endif; ?>
<main class="page">
<?php foreach (take_flashes() as $f): ?>
  <div class="flash flash-<?= h($f['soort']) ?>" role="status"><?= h($f['tekst']) ?></div>
<?php endforeach;
}

function admin_foot(): void
{
    ?>
</main>
</body>
</html>
<?php
}
