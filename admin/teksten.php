<?php
require __DIR__ . '/../includes/auth.php';
require __DIR__ . '/../includes/admin_layout.php';

// Alle velden op deze pagina: sleutel => [label, uitleg, soort]
$groepen = [
    'Bovenaan de site' => [
        'badge'      => ['Labeltje', 'Klein label boven de titel, bv. "Nieuw in Kapellen". Leeg = geen label.', 'text'],
        'hero_titel' => ['Titel', '', 'text'],
        'hero_tekst' => ['Korte tekst onder de titel', 'Wordt ook gebruikt als beschrijving in Google.', 'textarea'],
    ],
    'Ons verhaal' => [
        'verhaal_titel'     => ['Titel', '', 'text'],
        'verhaal_tekst'     => ['Tekst', 'Laat een lege regel tussen twee alinea\'s.', 'textarea'],
        'verhaal_kenmerken' => ['Kenmerken', 'De bolletjes onder de tekst, gescheiden door komma\'s.', 'text'],
    ],
    'Kaart' => [
        'kaart_intro' => ['Tekst boven de kaart', '', 'textarea'],
    ],
    'Adres & contact' => [
        'adres_straat'   => ['Straat en nummer', '', 'text'],
        'adres_gemeente' => ['Postcode en gemeente', '', 'text'],
        'email'          => ['E-mailadres', 'Leeg = niet tonen.', 'email'],
        'telefoon'       => ['Telefoonnummer', 'Leeg = niet tonen.', 'text'],
        'instagram'      => ['Instagram-naam', 'Zonder @, bv. coffeeclubcoco', 'text'],
    ],
    'Reservaties' => [
        'zenchef_id' => ['Zenchef restaurant-ID', 'Het nummer uit je Zenchef-account. Ingevuld = er verschijnen "Reserveer een tafel"-knoppen. Leeg = geen knoppen.', 'text'],
    ],
];

$fout = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $email = post('email');
    if ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $fout = 'Het e-mailadres lijkt niet te kloppen.';
    } else {
        foreach ($groepen as $velden) {
            foreach ($velden as $sleutel => $_) {
                $waarde = post($sleutel);
                if ($sleutel === 'instagram') {
                    $waarde = ltrim($waarde, '@');
                }
                save_setting($sleutel, $waarde);
            }
        }
        flash('Teksten en gegevens opgeslagen.');
        redirect('teksten.php');
    }
}

admin_head('Teksten & gegevens', 'teksten');
?>
<div class="page-head">
  <div>
    <h1>Teksten &amp; gegevens</h1>
    <p>Pas de teksten op de website aan. Klik onderaan op "Opslaan".</p>
  </div>
</div>
<?php if ($fout): ?><div class="flash flash-fout"><?= h($fout) ?></div><?php endif; ?>

<form method="post" class="stack">
  <?= csrf_field() ?>
  <?php foreach ($groepen as $titel => $velden): ?>
  <div class="card">
    <h2><?= h($titel) ?></h2>
    <div class="stack">
      <?php foreach ($velden as $sleutel => [$label, $uitleg, $soort]):
        $waarde = $_SERVER['REQUEST_METHOD'] === 'POST' ? post($sleutel) : setting($sleutel); ?>
        <label><?= h($label) ?>
          <?php if ($uitleg): ?><span class="hint"><?= h($uitleg) ?></span><?php endif; ?>
          <?php if ($soort === 'textarea'): ?>
            <textarea name="<?= $sleutel ?>" id="<?= $sleutel ?>" rows="<?= $sleutel === 'verhaal_tekst' ? 8 : 3 ?>"><?= h($waarde) ?></textarea>
          <?php else: ?>
            <input type="<?= $soort ?>" name="<?= $sleutel ?>" id="<?= $sleutel ?>" value="<?= h($waarde) ?>">
          <?php endif; ?>
        </label>
      <?php endforeach; ?>
    </div>
  </div>
  <?php endforeach; ?>
  <div class="actions"><button class="btn" type="submit">Opslaan</button></div>
</form>
<?php admin_foot(); ?>
