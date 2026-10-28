<?php
require __DIR__ . '/../includes/auth.php';
require __DIR__ . '/../includes/admin_layout.php';

// Snel aanpassen: pastry van de week en melding
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    save_setting('pastry_van_de_week', post('pastry_van_de_week'));
    save_setting('melding', post('melding'));
    flash('Opgeslagen. Je ziet het meteen op de site.');
    redirect('index.php');
}

$aantalItems = (int) db()->query('SELECT COUNT(*) FROM menu_items')->fetchColumn();
$aantalVerborgen = (int) db()->query('SELECT COUNT(*) FROM menu_items WHERE zichtbaar = 0')->fetchColumn();
$aantalFotos = (int) db()->query('SELECT COUNT(*) FROM photos WHERE zichtbaar = 1')->fetchColumn();
$vandaag = opening_hours()[(int) date('w')];

admin_head('Overzicht', 'dashboard');
?>
<div class="page-head">
  <div>
    <h1>Hallo <?= h($huidigeGebruiker['naam']) ?></h1>
    <p>Wat wil je vandaag aanpassen? Alles wat je opslaat, staat meteen op de website.</p>
  </div>
</div>

<div class="card">
  <h2>Snel aanpassen</h2>
  <form class="stack" method="post">
    <?= csrf_field() ?>
    <label>Pastry van de week
      <span class="hint">Bijvoorbeeld "Pecan bun met ahornsiroop". Laat leeg om niets te tonen.</span>
      <input type="text" name="pastry_van_de_week" id="pastry_van_de_week" value="<?= h(setting('pastry_van_de_week')) ?>" maxlength="120">
    </label>
    <label>Melding bovenaan de site
      <span class="hint">Bijvoorbeeld "Op 1 november zijn we gesloten". Laat leeg om geen melding te tonen.</span>
      <input type="text" name="melding" id="melding" value="<?= h(setting('melding')) ?>" maxlength="200">
    </label>
    <div class="actions"><button class="btn" type="submit">Opslaan</button></div>
  </form>
</div>

<div class="grid-3">
  <a class="card tile" href="kaart.php">
    <b>Kaart</b>
    <span><?= $aantalItems ?> items<?= $aantalVerborgen ? ", waarvan $aantalVerborgen verborgen" : '' ?>. Prijzen, nieuwe drankjes en favorieten.</span>
  </a>
  <a class="card tile" href="uren.php">
    <b>Openingsuren</b>
    <span>Vandaag (<?= h(strtolower($vandaag['naam'])) ?>):
      <?= $vandaag['gesloten'] ? 'gesloten' : h(tijd($vandaag['open']) . ' – ' . tijd($vandaag['sluit'])) ?></span>
  </a>
  <a class="card tile" href="fotos.php">
    <b>Foto's</b>
    <span><?= $aantalFotos ?> foto's op de site. Nieuwe foto's toevoegen of verbergen.</span>
  </a>
  <a class="card tile" href="teksten.php">
    <b>Teksten &amp; gegevens</b>
    <span>Ons verhaal, adres, e-mail, telefoon, Instagram en reservaties.</span>
  </a>
</div>
<?php admin_foot(); ?>
