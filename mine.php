<?php
session_start();
header('Cache-Control: no-cache, must-revalidate');
header('Expires: 0');

define('MADUM_MINER_COLS', 12);
define('MADUM_MINER_ROWS_SURFACE', 8);
define('MADUM_MINER_ROWS_MINE', 6);

$batiments = [
    'copper' => 'atelier',
    'iron' => 'usine',
    'gem' => 'banque',
    'artifact' => 'musee'
];
$batiment_libelle = [
    'atelier'   => 'Atelier',
    'usine'     => 'Usine',
    'banque'    => 'Banque',
    'musee'     => 'Musée',
    'generator' => 'Générateur',
    'storage'   => 'Entrepôt'
];
$batiment_icone = [
    'atelier'   => '🛠️',
    'usine'     => '🏭',
    'banque'    => '🏦',
    'musee'     => '🏺',
    'generator' => '🔋',
    'storage'   => '📦'
];
$batiment_bootstrap = [
    'atelier'   => 'bg-warning text-dark',
    'usine'     => 'bg-secondary text-white',
    'banque'    => 'bg-success text-white',
    'musee'     => 'bg-info text-dark',
    'generator' => 'bg-danger text-white',
    'storage'   => 'bg-primary text-white'
];
$batiment_tailles = [
    'atelier'   => [2,1],
    'usine'     => [3,1],
    'banque'    => [2,2],
    'musee'     => [3,2],
    'storage'   => [1,1],
    'generator' => [1,1]
];
$ressource_libelle = [
    'copper'   => 'Cuivre',
    'iron'     => 'Fer',
    'gem'      => 'Gemme',
    'artifact' => 'Artefact',
    'rock'     => 'Roche'
];
$ressource_icone = [
    'copper'   => '🔩',
    'iron'     => '⚙️',
    'gem'      => '💎',
    'artifact' => '🧭',
    'rock'     => '⛏️'
];
$ressource_bootstrap = [
    'copper'   => 'bg-warning text-dark',
    'iron'     => 'bg-secondary text-white',
    'gem'      => 'bg-success text-white',
    'artifact' => 'bg-info text-dark',
    'rock'     => 'bg-dark text-white'
];
// Points
$points_ressource = [
    'copper'   => 1,
    'iron'     => 2,
    'gem'      => 4,
    'artifact' => 8,
    'rock'     => 0
];
$points_batiment = [
    'atelier'   => 3,
    'usine'     => 5,
    'banque'    => 7,
    'musee'     => 10,
    'storage'   => 0,
    'generator' => 0
];

// Aucune case vide : tout ce qui n'est pas ressource est roche
function init_game($niveau=1) {
    if (function_exists('random_int')) {
        srand(random_int(PHP_INT_MIN, PHP_INT_MAX));
    } else {
        srand((int)(microtime(true)*1000000));
    }
    $_SESSION['surface'] = array_fill(0, MADUM_MINER_ROWS_SURFACE, array_fill(0, MADUM_MINER_COLS, 'empty'));
    $_SESSION['surface'][2][5] = 'generator';
    $_SESSION['surface'][3][5] = 'storage';
    $_SESSION['mine'] = [];
    $total_cases = MADUM_MINER_ROWS_MINE * MADUM_MINER_COLS;
    $copper = 8;
    $iron = 6;
    $gem = 2;
    $artifact = 1;
    $base_rock = $total_cases - $copper - $iron - $gem - $artifact;
    $resources = [
        'copper' => $copper,
        'iron' => $iron,
        'gem' => $gem,
        'artifact' => $artifact,
        'rock' => $base_rock // tout le reste = roche, aucune case vide
    ];
    $pool = [];
    foreach ($resources as $type => $count) {
        for ($i = 0; $i < $count; $i++) $pool[] = $type;
    }
    shuffle($pool);
    $i = 0;
    for ($y = 0; $y < MADUM_MINER_ROWS_MINE; $y++) {
        for ($x = 0; $x < MADUM_MINER_COLS; $x++) {
            $_SESSION['mine'][$y][$x] = [
                'discovered' => false,
                'resource' => $pool[$i++]
            ];
        }
    }
    $_SESSION['inventaire'] = ['copper'=>0,'iron'=>0,'gem'=>0,'artifact'=>0];
    $_SESSION['placement'] = [];
    $_SESSION['message'] = null;
}
function can_place_building($bat, $y, $x, &$isBlockedByRock = null, $rotation = 0) {
    global $batiment_tailles;
    $isBlockedByRock = false;
    if (!isset($batiment_tailles[$bat])) return false;
    list($h,$w) = $batiment_tailles[$bat];
    if ($rotation) list($h,$w) = [$w,$h];
    // Hors limite ?
    if ($y + $h > MADUM_MINER_ROWS_SURFACE || $x + $w > MADUM_MINER_COLS) return false;
    // Collision ?
    for ($dy=0; $dy<$h; $dy++) {
        for ($dx=0; $dx<$w; $dx++) {
            $ny = $y + $dy; $nx = $x + $dx;
            if ($_SESSION['surface'][$ny][$nx] !== 'empty') return false;
            if ($ny < MADUM_MINER_ROWS_MINE && $_SESSION['mine'][$ny][$nx]['discovered'] && $_SESSION['mine'][$ny][$nx]['resource']=='rock') {
                $isBlockedByRock = true;
                return false;
            }
        }
    }
    // Proximité : sauf pour le tout premier bâtiment (hors storage/generator)
    if (!building_proximity($y, $x, $h, $w)) return false;
    return true;
}
// Proximité : sauf si aucun bâtiment posé (hors generator/storage)
function building_proximity($y, $x, $h, $w) {
    global $batiment_libelle;
    $first_building = true;
    for ($row=0; $row<MADUM_MINER_ROWS_SURFACE; $row++) {
        for ($col=0; $col<MADUM_MINER_COLS; $col++) {
            $cell = $_SESSION['surface'][$row][$col];
            if ($cell !== 'empty' && $cell !== 'generator' && $cell !== 'storage')
                $first_building = false;
        }
    }
    if ($first_building) return true;
    // Check si au moins une case adjacente à la zone cible est occupée
    for ($dy=0; $dy<$h; $dy++) for ($dx=0; $dx<$w; $dx++) {
        $ny = $y + $dy; $nx = $x + $dx;
        $adj = [
            [$ny-1,$nx],[$ny+1,$nx],[$ny,$nx-1],[$ny,$nx+1]
        ];
        foreach ($adj as $a) {
            list($ay,$ax) = $a;
            if ($ay >= 0 && $ay < MADUM_MINER_ROWS_SURFACE && $ax >= 0 && $ax < MADUM_MINER_COLS) {
                $cell = $_SESSION['surface'][$ay][$ax];
                if ($cell !== 'empty' && $cell !== 'generator' && $cell !== 'storage') return true;
            }
        }
    }
    return false;
}
function place_building_on_grid($bat, $y, $x, $rotation=0) {
    global $batiment_tailles;
    list($h,$w) = $batiment_tailles[$bat];
    if ($rotation) list($h,$w) = [$w,$h];
    for ($dy=0; $dy<$h; $dy++) {
        for ($dx=0; $dx<$w; $dx++) {
            $_SESSION['surface'][$y+$dy][$x+$dx] = $bat;
        }
    }
}
function get_niveau() {
    return isset($_SESSION['niveau']) ? $_SESSION['niveau'] : 1;
}
function get_score() {
    return isset($_SESSION['score']) ? $_SESSION['score'] : 0;
}
function inc_score($nb) {
    $_SESSION['score'] = get_score() + $nb;
}
function inc_niveau() {
    $_SESSION['niveau'] = get_niveau() + 1;
}
if (!isset($_SESSION['surface']) || !isset($_SESSION['mine']) || !isset($_SESSION['placement']) || !isset($_SESSION['inventaire']) || !isset($_SESSION['score']) || !isset($_SESSION['niveau'])) {
    $_SESSION['score'] = 0;
    $_SESSION['niveau'] = 1;
    init_game(1);
}
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] === 'mine' && isset($_POST['pos'])) {
        list($y, $x) = explode('-', $_POST['pos']);
        $cell = &$_SESSION['mine'][$y][$x];
        if (!$cell['discovered']) {
            $cell['discovered'] = true;
            $r = $cell['resource'];
            if (isset($_SESSION['inventaire'][$r])) $_SESSION['inventaire'][$r]++;
            global $points_ressource, $batiments;
            if (isset($points_ressource[$r])) inc_score($points_ressource[$r]);
            if (isset($batiments[$r])) {
                $_SESSION['placement'][] = $batiments[$r];
            }
        }
        $pendingBat = count($_SESSION['placement']) ? $_SESSION['placement'][0] : null;
        ajax_return($pendingBat);
    }
    if ($_POST['action'] === 'place' && isset($_POST['pos']) && isset($_POST['batiment'])) {
        $bat = $_POST['batiment'];
        $rotation = isset($_POST['rotation']) ? (int)$_POST['rotation'] : 0;
        list($y, $x) = explode('-', $_POST['pos']);
        $isBlockedByRock = false;
        $ok = can_place_building($bat, $y, $x, $isBlockedByRock, $rotation);
        if ($ok) {
            place_building_on_grid($bat, $y, $x, $rotation);
            array_shift($_SESSION['placement']);
            global $points_batiment;
            if (isset($points_batiment[$bat])) inc_score($points_batiment[$bat]);
            $_SESSION['message'] = ['success','Bâtiment posé ! (+'.($points_batiment[$bat]??0).' pts)'];
        } else {
            $_SESSION['message'] = [
                'danger',
                $isBlockedByRock
                    ? 'Impossible de poser ici : une roche découverte bloque la construction à cet endroit (malus) !'
                    : 'Emplacement invalide pour ce bâtiment. Respecte la proximité et la place libre.'
            ];
        }
        $pendingBat = count($_SESSION['placement']) ? $_SESSION['placement'][0] : null;
        ajax_return($pendingBat);
    }
    if ($_POST['action'] === 'reset') {
        $_SESSION['score'] = 0;
        $_SESSION['niveau'] = 1;
        session_destroy();
        session_start();
        $_SESSION['score'] = 0;
        $_SESSION['niveau'] = 1;
        init_game(1);
        ajax_return(null);
    }
    if ($_POST['action'] === 'nextlevel') {
        inc_niveau();
        init_game(get_niveau());
        ajax_return(null);
    }
    exit;
}
function ajax_return($pendingBat) {
    echo json_encode([
        'html' => render_game($pendingBat),
        'batiment' => $pendingBat
    ]);
    exit;
}
function game_is_blocked() {
    foreach ($_SESSION['mine'] as $row)
        foreach ($row as $cell)
            if (!$cell['discovered'])
                return false;
    if (count($_SESSION['placement']) > 0) {
        foreach ($_SESSION['placement'] as $bat) {
            global $batiment_tailles;
            for ($rot=0; $rot<2; $rot++) {
                list($h,$w) = $batiment_tailles[$bat];
                if ($rot) list($h,$w) = [$w,$h];
                for ($y = 0; $y <= MADUM_MINER_ROWS_SURFACE - $h; $y++) {
                    for ($x = 0; $x <= MADUM_MINER_COLS - $w; $x++) {
                        $tmp = false;
                        if (can_place_building($bat, $y, $x, $tmp, $rot)) {
                            return false;
                        }
                    }
                }
            }
        }
    }
    return true;
}
function render_game($pendingBat) {
    global $batiment_libelle, $batiment_bootstrap, $batiment_icone, $batiment_tailles, $ressource_libelle, $ressource_icone, $ressource_bootstrap;
    ob_start();
    ?>
    <div class="mb-3 text-center">
        <span class="badge bg-success bg-opacity-10 text-success border border-success fs-5 me-2">
            🏆 Score
        </span>
        <span class="badge bg-light text-dark border border-success fs-6 me-3">
            <?= get_score() ?>
        </span>
        <span class="badge bg-primary bg-opacity-10 text-primary border border-primary fs-5 me-2">
            🎯 Niveau
        </span>
        <span class="badge bg-light text-dark border border-primary fs-6 me-3">
            <?= get_niveau() ?>
        </span>
        <button class="btn btn-warning ms-3" onclick="resetGame()">🔄 Nouvelle partie</button>
    </div>
    <?php if ($_SESSION['message']) : 
        list($alert, $text) = $_SESSION['message'];
    ?>
        <div id="game-alert" class="alert alert-<?=htmlspecialchars($alert)?> text-center fw-bold fs-5 mx-auto" style="max-width:700px; margin-top:30px;">
            <?=htmlspecialchars($text)?>
        </div>
        <script>
        setTimeout(() => {
            const alertBox = document.getElementById('game-alert');
            if(alertBox) alertBox.scrollIntoView({behavior:'smooth', block:'center'});
        }, 100);
        </script>
    <?php $_SESSION['message']=null; endif; ?>
    <div class="mb-3 text-center">
        <span class="badge bg-secondary fs-5">Inventaire</span>
        <?php foreach ($_SESSION['inventaire'] as $res=>$nb): ?>
            <span class="badge border fs-6 ms-2"><?= $ressource_icone[$res] ?> <?= $ressource_libelle[$res] ?> : <b><?= $nb ?></b></span>
        <?php endforeach; ?>
    </div>

    <!-- Mine (souterrain) en haut -->
    <div class="row justify-content-center g-3">
        <div class="col-auto">
            <div class="text-center mb-2 fw-bold">Mine (souterrain)</div>
            <table class="table table-bordered table-sm align-middle mb-0" style="background:#fff;max-width:fit-content">
            <tbody>
            <?php
            for ($y = 0; $y < MADUM_MINER_ROWS_MINE; $y++) {
                echo '<tr>';
                for ($x = 0; $x < MADUM_MINER_COLS; $x++) {
                    $cell = $_SESSION['mine'][$y][$x];
                    if ($cell['discovered']) {
                        $resource = $cell['resource'];
                        $icon = $ressource_icone[$resource];
                        $libelle = $ressource_libelle[$resource];
                        $colorClass = $ressource_bootstrap[$resource];
                        echo "<td class='text-center align-middle rounded-2 $colorClass' style='width:6.3rem;height:2.6rem'>";
                        echo "<span title='$libelle' class='fs-6'>$icon</span> <span class='small'>$libelle</span>";
                        echo "</td>";
                    } else {
                        if ($pendingBat) {
                            echo "<td class='bg-secondary opacity-25' style='width:6.3rem;height:2.6rem'></td>";
                        } else {
                            echo "<td style='width:6.3rem;height:2.6rem'><button type='button' class='btn btn-light btn-sm mine-btn rounded-pill' style='width:100%;height:2.4rem' data-pos='$y-$x' title='Miner ici'>⛏️ Miner</button></td>";
                        }
                    }
                }
                echo '</tr>';
            }
            ?>
            </tbody>
            </table>
        </div>
    </div>
    <!-- Surface (ville) en bas -->
    <div class="row justify-content-center g-3 mt-4">
        <div class="col-auto">
            <div id="surface" class="<?= $pendingBat ? 'border border-info rounded p-2 mb-3' : '' ?>">
                <div class="text-center mb-2 fw-bold">Surface (ville)</div>
                <div class="text-center small text-muted mb-2">
                    Les bâtiments occupent plusieurs cases selon leur rareté : plus ils sont rares, plus ils prennent de place.<br>
                    <span class="fw-bold">Entrepôt 1x1, Atelier 2x1, Usine 3x1, Banque 2x2, Musée 3x2</span>
                </div>
                <div class="text-center mb-2">
                    <button class="btn btn-info btn-sm" id="rotate-btn" type="button">↔️ Tourner</button>
                    <span class="ms-2 small text-muted" id="rotate-txt">(pose en largeur)</span>
                </div>
                <table class="table table-bordered table-sm align-middle mb-0" style="background:#fff;max-width:fit-content">
                <tbody>
                <?php
                for ($y = 0; $y < MADUM_MINER_ROWS_SURFACE; $y++) {
                    echo '<tr>';
                    for ($x = 0; $x < MADUM_MINER_COLS; $x++) {
                        $cell = $_SESSION['surface'][$y][$x];
                        $libelle = isset($batiment_libelle[$cell]) ? $batiment_libelle[$cell] : '';
                        $icon = $cell !== 'empty' && isset($batiment_icone[$cell]) ? $batiment_icone[$cell] : '';
                        $bootstrapClass = $cell !== 'empty' ? $batiment_bootstrap[$cell] : 'bg-light';
                        $cellClass = "surface-cell $bootstrapClass";
                        $extra = "";
                        $isBlockedByRock = ($y < MADUM_MINER_ROWS_MINE && $_SESSION['mine'][$y][$x]['discovered'] && $_SESSION['mine'][$y][$x]['resource']=='rock');
                        $isPlacable = false;
                        if ($pendingBat) {
                            $tmp = false;
                            // On envoie rotation via JS, donc la surbrillance sera faite JS side
                            $extra = " data-pos='$y-$x'";
                        }
                        echo "<td class='$cellClass text-center align-middle rounded-2' style='width:6.3rem;height:2.6rem;vertical-align:middle;'$extra>";
                        if ($icon && $libelle) {
                            echo "<span title='$libelle' class='fs-6'>$icon</span> <span class='small'>$libelle</span>";
                        }
                        echo "</td>";
                    }
                    echo '</tr>';
                }
                ?>
                </tbody>
                </table>
            </div>
        </div>
    </div>
    <?php
    if (game_is_blocked()) {
        ?>
        <div class="alert alert-success text-center my-4 fs-5">
            Niveau terminé !<br>
            <button class="btn btn-primary mt-3" onclick="nextLevel()">🚀 Passer au niveau suivant</button>
        </div>
        <?php
    }
    return ob_get_clean();
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>MaduMiner – Jeu minier 2D Far West Steampunk</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <style>
        body { background: #f8fafc; }
        .badge { font-weight: 600; letter-spacing: .02em; }
        .surface-cell.preview-ok { outline: 2.5px solid #17c964 !important; }
        .surface-cell.preview-bad { outline: 2.5px solid #c93c17 !important; }
    </style>
</head>
<body>
<div class="container py-4">
    <h1 class="mb-3 text-center">MaduMiner <span class="badge bg-info">Far West Steampunk</span></h1>
    <div id="game">
        <?php
        echo render_game(count($_SESSION['placement']) ? $_SESSION['placement'][0] : null);
        ?>
    </div>
</div>
<script>
let pendingBuilding = <?= json_encode(count($_SESSION['placement']) ? $_SESSION['placement'][0] : null); ?>;
let rotation = 0;
document.addEventListener("DOMContentLoaded", ()=> {
    attachEvents();
});
function mineCase(y, x) {
    fetch(window.location.href, {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: 'action=mine&pos=' + y + '-' + x
    })
    .then(r => r.json())
    .then(data => {
        document.getElementById('game').innerHTML = data.html;
        pendingBuilding = data.batiment ?? null;
        attachEvents();
    });
}
function placeBuilding(y, x, batiment, rotation=0) {
    fetch(window.location.href, {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: 'action=place&pos=' + y + '-' + x + '&batiment=' + batiment + '&rotation=' + rotation
    })
    .then(r => r.json())
    .then(data => {
        document.getElementById('game').innerHTML = data.html;
        pendingBuilding = data.batiment ?? null;
        attachEvents();
    });
}
function resetGame() {
    fetch(window.location.href, {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: 'action=reset'
    })
    .then(r => r.json())
    .then(data => {
        pendingBuilding = data.batiment ?? null;
        document.getElementById('game').innerHTML = data.html;
        attachEvents();
    });
}
function nextLevel() {
    fetch(window.location.href, {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: 'action=nextlevel'
    })
    .then(r => r.json())
    .then(data => {
        pendingBuilding = data.batiment ?? null;
        document.getElementById('game').innerHTML = data.html;
        attachEvents();
    });
}

// --- ROTATION + PREVIEW ---
function attachEvents() {
    // Bouton rotation
    let rotateBtn = document.getElementById('rotate-btn');
    let rotateTxt = document.getElementById('rotate-txt');
    if (rotateBtn) {
        rotateBtn.onclick = function() {
            rotation = 1 - rotation;
            if (rotateTxt) rotateTxt.innerHTML = rotation ? "(pose en hauteur)" : "(pose en largeur)";
        }
    }
    // Surbrillance/preview
    document.querySelectorAll('.surface-cell').forEach(cell => {
        cell.onmouseenter = function() {
            highlightArea(this, true);
        };
        cell.onmouseleave = function() {
            highlightArea(this, false);
        };
        cell.onclick = function() {
            let pos = this.getAttribute('data-pos');
            if (!pos) return;
            let [y, x] = pos.split('-');
            if (pendingBuilding) {
                placeBuilding(y, x, pendingBuilding, rotation);
            }
        };
    });
    document.querySelectorAll('.mine-btn').forEach(btn => {
        btn.onclick = function() {
            let [y, x] = this.dataset.pos.split('-');
            mineCase(y, x);
        };
    });
}
function highlightArea(cell, show) {
    if (!pendingBuilding) return;
    let pos = cell.getAttribute('data-pos');
    if (!pos) return;
    let [y, x] = pos.split('-').map(Number);
    // Tailles de bâtiment, rotation
    let tailles = {
        'atelier':[2,1], 'usine':[3,1], 'banque':[2,2], 'musee':[3,2], 'storage':[1,1], 'generator':[1,1]
    };
    let [h,w] = tailles[pendingBuilding];
    if (rotation) [h,w] = [w,h];
    let ok = true;
    for (let dy=0; dy<h; dy++) for (let dx=0; dx<w; dx++) {
        let cy = y+dy, cx = x+dx;
        let selector = `.surface-cell[data-pos="${cy}-${cx}"]`;
        let c = document.querySelector(selector);
        if (!c) ok = false;
        // On checke si la case est libre
        if (c && c.classList.contains('bg-light')) { }
        else if (c && c.classList.contains('bg-light')===false) ok = false;
    }
    // Surbrillance verte si ok, rouge sinon
    for (let dy=0; dy<h; dy++) for (let dx=0; dx<w; dx++) {
        let cy = y+dy, cx = x+dx;
        let selector = `.surface-cell[data-pos="${cy}-${cx}"]`;
        let c = document.querySelector(selector);
        if (c) {
            if (show) {
                c.classList.add(ok ? "preview-ok" : "preview-bad");
            } else {
                c.classList.remove("preview-ok");
                c.classList.remove("preview-bad");
            }
        }
    }
}
</script>
</body>
</html>
