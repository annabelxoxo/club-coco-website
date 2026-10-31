<?php
require __DIR__ . '/includes/bootstrap.php';

// Alles wat op de pagina komt, haalt PHP hier uit de database
$kolommen     = menu_columns();
$uren         = opening_hours();
$fotosVerhaal = photos('verhaal');
$fotosKaart   = photos('kaart');
$fotosGalerij = photos('galerij');

$melding    = setting('melding');
$pastry     = setting('pastry_van_de_week');
$instagram  = ltrim(setting('instagram', 'coffeeclubcoco'), '@');
$zenchefId  = setting('zenchef_id');
$email      = setting('email');
$telefoon   = setting('telefoon');
$straat     = setting('adres_straat');
$gemeente   = setting('adres_gemeente');
$kenmerken  = array_filter(array_map('trim', explode(',', setting('verhaal_kenmerken'))));
$routeUrl   = 'https://www.google.com/maps/search/?api=1&query=' . rawurlencode($straat . ', ' . $gemeente);
$reserveUrl = $zenchefId !== '' ? 'https://bookings.zenchef.com/results?rid=' . rawurlencode($zenchefId) . '&lang=nl' : '';
$vandaag    = (int) date('w');
?>
<!doctype html>
<html lang="nl">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1,viewport-fit=cover">
<title>club Coco · Coffee, breakfast &amp; lunch in Kapellen</title>
<meta name="description" content="<?= h(setting('hero_tekst')) ?>">
<link rel="icon" href="images/logo.png">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Bagel+Fat+One&family=Figtree:wght@400;500;700&family=DM+Mono:wght@400;500&display=swap">
<link rel="stylesheet" href="assets/css/style.css">
</head>
<body>

<a class="skip" href="#inhoud">Naar de inhoud</a>

<?php if ($melding !== ''): ?>
<div class="melding"><div class="wrap"><?= h($melding) ?></div></div>
<?php endif; ?>

<nav aria-label="Hoofdmenu">
  <div class="wrap">
    <a class="logo" href="#top"><img src="images/logo-nav.png" alt="club Coco" width="560" height="186"></a>
    <ul>
      <li><a href="#verhaal">Ons verhaal</a></li>
      <li><a href="#menu">Menu</a></li>
      <li><a href="#fotos">Foto's</a></li>
      <li><a href="#bezoek">Bezoek ons</a></li>
    </ul>
  </div>
</nav>

<main id="inhoud">

<header class="hero" id="top">
  <div class="wrap hero-grid">
    <div>
      <img class="logo-img" src="images/logo-licht.png" alt="club Coco: coffee, breakfast, lunch">
      <?php if (setting('badge') !== ''): ?>
        <div class="badge"><i></i> <?= h(setting('badge')) ?></div>
      <?php endif; ?>
      <h1><?= h(setting('hero_titel')) ?></h1>
      <p class="lead"><?= h(setting('hero_tekst')) ?></p>
      <div class="cta-row">
        <?php if ($reserveUrl): ?>
          <a class="btn" href="<?= h($reserveUrl) ?>" target="_blank" rel="noopener">Reserveer een tafel<span class="sr-only"> (opent in nieuw venster)</span></a>
        <?php else: ?>
          <a class="btn" href="#bezoek">Kom langs</a>
        <?php endif; ?>
        <a class="btn ghost" href="#menu">Bekijk de kaart</a>
      </div>
    </div>
  </div>
</header>

<div class="tiles" aria-hidden="true"></div>

<section class="band about" id="verhaal">
  <div class="wrap about-grid">
    <?php if ($fotosVerhaal): $f = $fotosVerhaal[0]; ?>
      <div class="about-photo"><img src="<?= h($f['pad']) ?>" alt="<?= h($f['alt']) ?>" loading="lazy"></div>
    <?php endif; ?>
    <div class="about-text">
      <p class="eyebrow">Ons verhaal</p>
      <h2><?= h(setting('verhaal_titel')) ?></h2>
      <?= paragraphs(setting('verhaal_tekst')) ?>
      <?php if ($kenmerken): ?>
        <ul class="facts">
          <?php foreach ($kenmerken as $k): ?><li><?= h($k) ?></li><?php endforeach; ?>
        </ul>
      <?php endif; ?>
    </div>
  </div>
</section>

<section class="band" id="menu">
  <div class="wrap">
    <div class="menu-head">
      <div>
        <p class="eyebrow">Wat we schenken</p>
        <h2>De kaart</h2>
        <p><?= str_replace('♥', '<span class="heart" aria-hidden="true">♥</span><span class="sr-only">hartje</span>', h(setting('kaart_intro'))) ?></p>
        <?php if ($pastry !== ''): ?>
          <p class="pastry-week"><b>Pastry van de week</b> <?= h($pastry) ?></p>
        <?php endif; ?>
      </div>
      <div class="steel" aria-hidden="true"><span><b>Coco</b>specialty coffee</span></div>
    </div>

    <?php if ($fotosKaart): ?>
    <div class="menu-photos">
      <?php foreach ($fotosKaart as $f): ?>
        <figure>
          <img src="<?= h($f['pad']) ?>" alt="<?= h($f['alt']) ?>" loading="lazy">
          <?php if ($f['label']): ?><figcaption><?= h($f['label']) ?></figcaption><?php endif; ?>
        </figure>
      <?php endforeach; ?>
    </div>
    <?php endif; ?>

    <div class="menu-grid">
      <?php foreach ($kolommen as $categorieen): ?>
      <div class="menu-col">
        <?php foreach ($categorieen as $cat): ?>
        <div class="cat"><h3><?= h($cat['naam']) ?></h3>
          <?php foreach ($cat['items'] as $item): ?>
            <div class="item">
              <span>
                <?php if ($item['favoriet']): ?><span class="heart" aria-hidden="true">♥</span><span class="sr-only">Favoriet van het huis:</span> <?php endif; ?>
                <?= h($item['naam']) ?>
                <?php if ($item['beschrijving']): ?><small><?= h($item['beschrijving']) ?></small><?php endif; ?>
              </span>
              <?php if ($item['prijs'] !== null && $item['prijs'] !== ''): ?>
                <span class="price"><?= h($item['prijs']) ?><span class="sr-only"> euro</span></span>
              <?php endif; ?>
            </div>
          <?php endforeach; ?>
          <?php if ($cat['notitie']): ?>
            <p class="<?= $cat['items'] ? 'catnote' : 'soon' ?>"><?= h($cat['notitie']) ?></p>
          <?php endif; ?>
        </div>
        <?php endforeach; ?>
      </div>
      <?php endforeach; ?>
    </div>
    <p class="note">Onze koffie is specialty coffee van <a href="https://www.crossroast.be/" target="_blank" rel="noopener"><strong>Crossroast</strong><span class="sr-only"> (opent in nieuw venster)</span></a>.</p>
  </div>
</section>

<div class="tiles" aria-hidden="true"></div>

<?php if ($fotosGalerij): ?>
<section class="band gallery" id="fotos">
  <div class="wrap">
    <p class="eyebrow">Een kijkje binnen</p>
    <h2>Foto's</h2>
    <div class="g-grid">
      <?php foreach ($fotosGalerij as $f):
        $klasse = ['breed' => 'g-big', 'hoog' => 'g-tall'][$f['formaat']] ?? ''; ?>
        <div class="<?= $klasse ?>"><img src="<?= h($f['pad']) ?>" alt="<?= h($f['alt']) ?>" loading="lazy"></div>
      <?php endforeach; ?>
    </div>
  </div>
</section>
<?php endif; ?>

<section class="band visit" id="bezoek">
  <div class="wrap visit-grid">
    <div>
      <p class="eyebrow">Bezoek ons</p>
      <h2>Kom langs in Kapellen</h2>
      <p class="addr"><?= h($straat) ?><br><?= h($gemeente) ?></p>
      <div class="cta-row">
        <a class="btn" href="<?= h($routeUrl) ?>" target="_blank" rel="noopener">Route plannen<span class="sr-only"> (opent in nieuw venster)</span></a>
        <?php if ($reserveUrl): ?>
          <a class="btn outline" href="<?= h($reserveUrl) ?>" target="_blank" rel="noopener">Reserveer een tafel<span class="sr-only"> (opent in nieuw venster)</span></a>
        <?php endif; ?>
      </div>
      <?php if ($email || $telefoon): ?>
        <ul class="contact-list">
          <?php if ($email): ?><li><a href="mailto:<?= h($email) ?>"><?= h($email) ?></a></li><?php endif; ?>
          <?php if ($telefoon): ?><li><a href="tel:<?= h(preg_replace('/[^0-9+]/', '', $telefoon)) ?>"><?= h($telefoon) ?></a></li><?php endif; ?>
        </ul>
      <?php endif; ?>
    </div>
    <div>
      <p class="eyebrow">Openingsuren</p>
      <table class="hours"><tbody>
        <?php foreach ($uren as $nr => $dag): ?>
          <tr data-d="<?= $nr ?>"<?= $nr === $vandaag ? ' class="today"' : '' ?>>
            <td><?= h($dag['naam']) ?><?= $nr === $vandaag ? '<span class="sr-only vandaag"> (vandaag)</span>' : '' ?></td>
            <td><?= $dag['gesloten'] ? 'Gesloten' : h(tijd($dag['open']) . ' – ' . tijd($dag['sluit'])) ?></td>
          </tr>
        <?php endforeach; ?>
      </tbody></table>
    </div>
  </div>
</section>

<section class="band insta">
  <div class="wrap">
    <div>
      <h2>Volg de club</h2>
      <p>Pastry van de week, nieuwe drankjes en sfeerbeelden: alles staat eerst op Instagram.</p>
    </div>
    <a class="btn" href="https://www.instagram.com/<?= h($instagram) ?>" target="_blank" rel="noopener">@<?= h($instagram) ?><span class="sr-only"> (opent in nieuw venster)</span></a>
  </div>
</section>

</main>

<footer>
  <div class="wrap">
    <span>© <?= date('Y') ?> club Coco · Kapellen</span>
    <span>Coffee · Breakfast · Lunch</span>
    <span class="credit">Made by Annabel Smeulders (annabelxoxo)</span>
  </div>
</footer>

<script>
// "vandaag" volgens de bezoeker; nodig voor de statische demo (en gecachte pagina's)
(function(){
  var rij=document.querySelector('.hours tr[data-d="'+new Date().getDay()+'"]'); if(!rij) return;
  document.querySelectorAll('.hours tr.today').forEach(function(tr){tr.classList.remove('today');});
  document.querySelectorAll('.hours .vandaag').forEach(function(el){el.remove();});
  rij.classList.add('today');
  rij.cells[0].insertAdjacentHTML('beforeend','<span class="sr-only vandaag"> (vandaag)</span>');
})();
</script>
</body>
</html>
