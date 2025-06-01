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
$batiment_formes = [
    'atelier'   => [
        [ [0,0],[0,1] ],
        [ [0,0],[1,0] ],
        [ [0,0],[1,0],[1,1] ],
    ],
    'usine'     => [
        [ [0,0],[1,0],[2,0] ],
        [ [0,0],[0,1],[0,2] ],
        [ [0,0],[1,0],[1,1],[1,2] ]
    ],
    'banque'    => [
        [ [0,0],[1,0],[0,1],[1,1] ],
        [ [0,0],[1,0],[2,0],[2,1] ]
    ],
    'musee'     => [
        [ [0,0],[1,0],[2,0],[2,1],[2,2] ],
        [ [0,0],[0,1],[0,2],[1,2],[2,2] ]
    ],
    'storage'   => [
        [ [0,0] ]
    ],
    'generator' => [
        [ [0,0] ]
    ]
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

function building_proximity_custom($cells) {
    foreach ($cells as $c) {
        $ny = $c[0]; $nx = $c[1];
        $adj = [
            [$ny-1,$nx],[$ny+1,$nx],[$ny,$nx-1],[$ny,$nx+1]
        ];
        foreach ($adj as $a) {
            list($ay,$ax) = $a;
            if ($ay >= 0 && $ay < MADUM_MINER_ROWS_SURFACE && $ax >= 0 && $ax < MADUM_MINER_COLS) {
                $cell = $_SESSION['surface'][$ay][$ax];
                if ($cell !== 'empty' && $cell !== 'rockmalus') {
                    return true;
                }
            }
        }
    }
    return false;
}
function can_place_building_custom($bat, $y, $x, $forme_idx, &$isBlockedByRock = null) {
    global $batiment_formes;
    $isBlockedByRock = false;
    if (!isset($batiment_formes[$bat][$forme_idx])) return false;
    $cells = [];
    foreach ($batiment_formes[$bat][$forme_idx] as $offset) {
        $ny = $y + $offset[0];
        $nx = $x + $offset[1];
        if ($ny >= MADUM_MINER_ROWS_SURFACE || $nx >= MADUM_MINER_COLS || $ny < 0 || $nx < 0)
            return false;
        if ($_SESSION['surface'][$ny][$nx] !== 'empty')
            return false;
        $cells[] = [$ny, $nx];
    }
    if (!building_proximity_custom($cells)) return false;
    return true;
}
function place_building_on_grid_custom($bat, $y, $x, $forme_idx) {
    global $batiment_formes;
    foreach ($batiment_formes[$bat][$forme_idx] as $offset) {
        $ny = $y + $offset[0];
        $nx = $x + $offset[1];
        $_SESSION['surface'][$ny][$nx] = $bat;
    }
}
function get_niveau() { return $_SESSION['niveau'] ?? 1; }
function get_score()  { return $_SESSION['score'] ?? 0; }
function inc_score($nb) { $_SESSION['score'] = get_score() + $nb; }
function inc_niveau() { $_SESSION['niveau'] = get_niveau() + 1; }
function init_game($niveau=1) {
    if (function_exists('random_int')) { srand(random_int(PHP_INT_MIN, PHP_INT_MAX)); }
    else { srand((int)(microtime(true)*1000000)); }
    $_SESSION['surface'] = array_fill(0, MADUM_MINER_ROWS_SURFACE, array_fill(0, MADUM_MINER_COLS, 'empty'));
    $_SESSION['surface'][2][5] = 'generator';
    $_SESSION['surface'][3][5] = 'storage';
    $_SESSION['mine'] = [];
    $total_cases = MADUM_MINER_ROWS_MINE * MADUM_MINER_COLS;
    $copper = 8; $iron = 6; $gem = 2; $artifact = 1;
    $base_rock = $total_cases - $copper - $iron - $gem - $artifact;
    $resources = ['copper' => $copper, 'iron' => $iron, 'gem' => $gem, 'artifact' => $artifact, 'rock' => $base_rock];
    $pool = [];
    foreach ($resources as $type => $count) for ($i = 0; $i < $count; $i++) $pool[] = $type;
    shuffle($pool); $i = 0;
    for ($y = 0; $y < MADUM_MINER_ROWS_MINE; $y++) for ($x = 0; $x < MADUM_MINER_COLS; $x++)
        $_SESSION['mine'][$y][$x] = ['discovered' => false, 'resource' => $pool[$i++]];
    $_SESSION['inventaire'] = ['copper'=>0,'iron'=>0,'gem'=>0,'artifact'=>0];
    $_SESSION['placement'] = [];
    $_SESSION['message'] = null;
}
if (!isset($_SESSION['surface']) || !isset($_SESSION['mine']) || !isset($_SESSION['placement']) || !isset($_SESSION['inventaire']) || !isset($_SESSION['score']) || !isset($_SESSION['niveau'])) {
    $_SESSION['score'] = 0; $_SESSION['niveau'] = 1; init_game(1);
}
if (!isset($_SESSION['gameover'])) $_SESSION['gameover'] = false;
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
            if (isset($batiments[$r])) $_SESSION['placement'][] = $batiments[$r];
            // Roche-malus à la verticale, avec proba qui augmente avec le niveau
            if ($r == 'rock') {
                $niveau = get_niveau();
                $chance = max(2, 8 - $niveau);
                if (rand(1, $chance) === 1) {
                    if ($y < MADUM_MINER_ROWS_SURFACE && $_SESSION['surface'][$y][$x] === 'empty') {
                        $_SESSION['surface'][$y][$x] = 'rockmalus';
                        $_SESSION['message'] = ['warning', "Attention ! Un éboulis rocheux est apparu en surface (malus bloquant) suite à l’extraction de la roche."];
                    }
                }
            }
        }
        $pendingBat = count($_SESSION['placement']) ? $_SESSION['placement'][0] : null;
        ajax_return($pendingBat);
    }
    if ($_POST['action'] === 'place' && isset($_POST['pos']) && isset($_POST['batiment'])) {
        $bat = $_POST['batiment'];
        $forme_idx = isset($_POST['forme']) ? (int)$_POST['forme'] : 0;
        list($y, $x) = explode('-', $_POST['pos']);
        $isBlockedByRock = false;
        $ok = can_place_building_custom($bat, $y, $x, $forme_idx, $isBlockedByRock);
        if ($ok) {
            place_building_on_grid_custom($bat, $y, $x, $forme_idx);
            array_shift($_SESSION['placement']);
            global $points_batiment;
            if (isset($points_batiment[$bat])) inc_score($points_batiment[$bat]);
            $_SESSION['message'] = ['success','Bâtiment posé ! (+'.($points_batiment[$bat]??0).' pts)'];
        } else {
            $_SESSION['message'] = [
                'danger',
                $isBlockedByRock
                    ? 'Impossible de poser ici : une roche découverte bloque la construction à cet endroit (malus) !'
                    : 'Emplacement invalide : le bâtiment doit toucher un autre bâtiment déjà posé.'
            ];
        }
        $pendingBat = count($_SESSION['placement']) ? $_SESSION['placement'][0] : null;
        ajax_return($pendingBat);
    }
    if ($_POST['action'] === 'reset') {
        $_SESSION['score'] = 0;
        $_SESSION['niveau'] = 1;
        $_SESSION['gameover'] = false;
        session_destroy();
        session_start();
        $_SESSION['score'] = 0;
        $_SESSION['niveau'] = 1;
        $_SESSION['gameover'] = false;
        init_game(1);
        ajax_return(null);
    }
    if ($_POST['action'] === 'nextlevel') {
        $encoreAMiner = false;
        foreach ($_SESSION['mine'] as $row)
            foreach ($row as $cell)
                if (!$cell['discovered'])
                    $encoreAMiner = true;
        if ($encoreAMiner) {
            $_SESSION['gameover'] = true;
        } else {
            inc_niveau();
            init_game(get_niveau());
        }
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
    global $batiment_formes;
    foreach ($_SESSION['mine'] as $row)
        foreach ($row as $cell)
            if (!$cell['discovered'])
                return false;
    if (count($_SESSION['placement']) > 0) {
        foreach ($_SESSION['placement'] as $bat) {
            foreach ($batiment_formes[$bat] as $fidx=>$forme) {
                for ($y = 0; $y < MADUM_MINER_ROWS_SURFACE; $y++) {
                    for ($x = 0; $x < MADUM_MINER_COLS; $x++) {
                        $tmp = false;
                        if (can_place_building_custom($bat, $y, $x, $fidx, $tmp)) {
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
    global $batiment_libelle, $batiment_bootstrap, $batiment_icone, $batiment_formes, $ressource_libelle, $ressource_icone, $ressource_bootstrap;
    ob_start();
    if (isset($_SESSION['gameover']) && $_SESSION['gameover']) {
        ?>
        <div class="alert alert-danger text-center my-4 fs-4 shadow">
            <b>Partie terminée !</b><br>
            Tu es bloqué alors qu'il reste des cases à miner.<br>
            Score final : <span class="fw-bold"><?= get_score() ?></span>
            <br>
            Niveau atteint : <span class="fw-bold"><?= get_niveau() ?></span><br>
            <button class="btn btn-warning mt-4" onclick="resetGame()">🔁 Recommencer une partie</button>
        </div>
        <script>
            setTimeout(()=>{document.querySelectorAll("button, .mine-btn, .surface-cell").forEach(e=>{e.disabled = true; e.onclick=null;});},500);
        </script>
        <?php
        return ob_get_clean();
    }
    ?>
    <div class="alert alert-info mx-auto mb-4 shadow-sm" style="max-width:900px;">
        <h4 class="mb-2 fw-bold">Règles du jeu MaduMiner</h4>
        <ul class="mb-1">
            <li><b>But :</b> Construis la plus grande ville robotique en optimisant la pose de tes bâtiments et en collectant un maximum de points !</li>
            <li><b>Extraction :</b> Clique sur les cases <span class="badge bg-light text-dark border">Miner</span> de la mine (en haut) pour découvrir des ressources (cuivre, fer, gemme, artefact) ou de la roche.</li>
            <li><b>Bâtiments :</b> À chaque découverte de ressource, tu obtiens un bâtiment à placer sur la surface (en bas) : chaque bâtiment a plusieurs formes (<b>barre, L, carré…</b>), utilise le bouton <b>↔️ Tourner la forme</b> pour optimiser la place !</li>
            <li><b>Prévisualisation :</b> Survole une case de la surface pour voir en couleur la zone où sera posé le bâtiment.<br>
                <span class="text-success">Vert : pose possible</span> | <span class="text-danger">Rouge : pose impossible</span>
            </li>
            <li><b>Contraintes :</b>
                <ul class="mb-1">
                    <li>Un bâtiment ne peut pas chevaucher un autre bâtiment ni une <b>roche (malus)</b> en surface.</li>
                    <li><b>Il doit toucher (haut/bas/gauche/droite) un autre bâtiment déjà posé.</b></li>
                </ul>
            </li>
            <li><b>Fin du jeu :</b>
                <ul class="mb-1">
                    <li>Tu passes au niveau suivant uniquement si tu as miné toute la mine.</li>
                    <li><b>Tu perds si tu ne peux plus rien poser alors qu'il reste des cases à miner.</b></li>
                </ul>
            </li>
            <li><b>Score & Niveau :</b> Chaque ressource/bâtiment rapporte des points. Enchaîne les niveaux pour battre ton record !</li>
        </ul>
        <div class="small text-muted">
            Astuce : teste toutes les formes possibles pour caser un maximum de bâtiments comme dans un Tetris géant !<br>
            <b>Bon jeu, Madum !</b>
        </div>
    </div>
    <div class="mb-3 text-center">
        <span class="badge bg-success bg-opacity-10 text-success border border-success fs-5 me-2">🏆 Score</span>
        <span class="badge bg-light text-dark border border-success fs-6 me-3"><?= get_score() ?></span>
        <span class="badge bg-primary bg-opacity-10 text-primary border border-primary fs-5 me-2">🎯 Niveau</span>
        <span class="badge bg-light text-dark border border-primary fs-6 me-3"><?= get_niveau() ?></span>
        <button class="btn btn-warning ms-3" onclick="resetGame()">🔄 Nouvelle partie</button>
    </div>
    <?php if ($_SESSION['message']) :
        list($alert, $text) = $_SESSION['message']; ?>
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
    <div class="row justify-content-center g-3 mt-4 align-items-center">
        <div class="col-auto">
            <div id="surface" class="<?= $pendingBat ? 'border border-info rounded p-2 mb-3' : '' ?>">
                <div class="text-center mb-2 fw-bold">Surface (ville)</div>
                <div class="text-center small text-muted mb-2">
                    Les bâtiments occupent plusieurs cases selon leur rareté et leur forme (L, barre, carré...) :<br>
                    <span class="fw-bold">Entrepôt 1x1, Atelier : 2 cases/ L, Usine : 3 cases/ L, Banque : carré/L, Musée : gros L</span>
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
                        if ($cell === 'generator' || $cell === 'storage') $cellClass .= " pivot-cell";
                        if ($cell === 'rockmalus') {
                            $cellClass = "surface-cell bg-dark text-white";
                            $icon = $ressource_icone['rock'];
                            $libelle = "Roche (malus)";
                            $extra = ' style="opacity:0.85;" title="Impossible de construire ici : roche (malus)"';
                        } elseif ($pendingBat) {
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
        <?php if ($pendingBat): 
            $bat_label = $batiment_libelle[$pendingBat];
            $bat_icon  = $batiment_icone[$pendingBat];
            $formes    = $batiment_formes[$pendingBat];
        ?>
        <div class="col-auto d-flex align-items-center">
            <div id="encart-batiment" class="text-center my-3">
                <div class="mx-auto p-3 rounded-3 shadow" style="background:#f0f0ff; border:2px solid #c3d1f7; min-width:220px; max-width:340px;">
                    <div class="fs-4 fw-bold mb-1">
                        <?= $bat_icon ?> <span style="letter-spacing:0.04em"><?= $bat_label ?></span>
                    </div>
                    <div class="small mb-2 text-muted">Sélectionne l'emplacement du bâtiment<br>sur la grille</div>
                    <div class="d-flex align-items-center justify-content-center gap-3 mb-2">
                        <button class="btn btn-info btn-lg shadow" id="rotate-btn" type="button" title="Tourner la forme (R)">
                            ↔️ Tourner
                        </button>
                        <div class="text-center" id="mini-forme"></div>
                    </div>
                    <div class="small text-muted" id="rotate-txt">(forme 1/<?=count($formes)?>)</div>
                </div>
            </div>
        </div>
        <?php endif; ?>
    </div>
    <?php
    $mine_fully_discovered = true;
    foreach ($_SESSION['mine'] as $row)
        foreach ($row as $cell)
            if (!$cell['discovered'])
                $mine_fully_discovered = false;
    if (game_is_blocked() && $mine_fully_discovered) {
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
        #encart-batiment { z-index:100; }
        #encart-batiment .btn-info { font-size:1.6rem; min-width:56px;}
        #encart-batiment .fs-4 { letter-spacing:.04em; }
        @media (max-width: 991px) {
            #encart-batiment { margin-left:0!important; margin-top:18px!important; }
            .row.align-items-center { flex-direction:column!important; }
        }
    </style>
</head>
<body>
<div class="container py-4">
    <h1 class="mb-3 text-center">MaduMiner <span class="badge bg-info">Far West Steampunk</span></h1>
    <div id="game">
        <?php echo render_game(count($_SESSION['placement']) ? $_SESSION['placement'][0] : null); ?>
    </div>
</div>
<script>
let pendingBuilding = <?= json_encode(count($_SESSION['placement']) ? $_SESSION['placement'][0] : null); ?>;
let formeIndex = 0;
let formesByBat = <?= json_encode([
    'atelier' => [
        [[0,0],[0,1]],
        [[0,0],[1,0]],
        [[0,0],[1,0],[1,1]]
    ],
    'usine' => [
        [[0,0],[1,0],[2,0]],
        [[0,0],[0,1],[0,2]],
        [[0,0],[1,0],[1,1],[1,2]]
    ],
    'banque' => [
        [[0,0],[1,0],[0,1],[1,1]],
        [[0,0],[1,0],[2,0],[2,1]]
    ],
    'musee' => [
        [[0,0],[1,0],[2,0],[2,1],[2,2]],
        [[0,0],[0,1],[0,2],[1,2],[2,2]]
    ],
    'storage' => [
        [[0,0]]
    ],
    'generator' => [
        [[0,0]]
    ]
]); ?>;
document.addEventListener("DOMContentLoaded", ()=> {
    attachEvents();
    window.formeIndex = 0;
    if(typeof renderMiniForme === "function") renderMiniForme();
    document.addEventListener("keydown", function(e){
        if (pendingBuilding && (e.key === "r" || e.key === "R")) {
            rotateForme();
            e.preventDefault();
        }
    });
});
function rotateForme() {
    let formes = formesByBat[pendingBuilding];
    formeIndex = (formeIndex + 1) % formes.length;
    window.formeIndex = formeIndex;
    let txt = document.getElementById("rotate-txt");
    if (txt) txt.innerHTML = "(forme " + (formeIndex+1) + "/" + formes.length + ")";
    if (typeof renderMiniForme === "function") renderMiniForme();
}
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
        formeIndex = 0;
        window.formeIndex = 0;
        attachEvents();
        if(typeof renderMiniForme === "function") renderMiniForme();
    });
}
function placeBuilding(y, x, batiment, formeIndex=0) {
    fetch(window.location.href, {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: 'action=place&pos=' + y + '-' + x + '&batiment=' + batiment + '&forme=' + formeIndex
    })
    .then(r => r.json())
    .then(data => {
        document.getElementById('game').innerHTML = data.html;
        pendingBuilding = data.batiment ?? null;
        formeIndex = 0;
        window.formeIndex = 0;
        attachEvents();
        if(typeof renderMiniForme === "function") renderMiniForme();
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
        formeIndex = 0;
        window.formeIndex = 0;
        document.getElementById('game').innerHTML = data.html;
        attachEvents();
        if(typeof renderMiniForme === "function") renderMiniForme();
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
        formeIndex = 0;
        window.formeIndex = 0;
        document.getElementById('game').innerHTML = data.html;
        attachEvents();
        if(typeof renderMiniForme === "function") renderMiniForme();
    });
}
function attachEvents() {
    let rotateBtn = document.getElementById('rotate-btn');
    let rotateTxt = document.getElementById('rotate-txt');
    if (rotateBtn && pendingBuilding && formesByBat[pendingBuilding].length > 1) {
        rotateBtn.disabled = false;
        rotateBtn.onclick = rotateForme;
    } else if (rotateBtn) {
        rotateBtn.disabled = true;
        if (rotateTxt) rotateTxt.innerHTML = "(forme 1/1)";
    }
    document.querySelectorAll('.surface-cell').forEach(cell => {
        cell.onmouseenter = function() { highlightArea(this, true); };
        cell.onmouseleave = function() { highlightArea(this, false); };
        cell.onclick = function() {
            let pos = this.getAttribute('data-pos');
            if (!pos) return;
            let [y, x] = pos.split('-');
            if (pendingBuilding) { placeBuilding(y, x, pendingBuilding, formeIndex); }
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
    let offsets = formesByBat[pendingBuilding][formeIndex];
    let ok = true;
    let gridOk = true;
    let cells = [];
    for (let off of offsets) {
        let cy = y + off[0], cx = x + off[1];
        let selector = `.surface-cell[data-pos="${cy}-${cx}"]`;
        let c = document.querySelector(selector);
        if (!c || !c.classList.contains('bg-light')) gridOk = false;
        cells.push([cy, cx]);
    }
    // Proximité : touche un bâtiment (ni empty, ni rockmalus)
    let touchesBat = false;
    for (let [cy, cx] of cells) {
        let adjacents = [
            [cy-1, cx], [cy+1, cx], [cy, cx-1], [cy, cx+1]
        ];
        for (let [ay, ax] of adjacents) {
            let adj = document.querySelector(`.surface-cell[data-pos="${ay}-${ax}"]`);
            if (adj && !adj.classList.contains('bg-light') && !adj.classList.contains('bg-dark')) {
                touchesBat = true;
            }
        }
    }
    ok = gridOk && touchesBat;
    for (let off of offsets) {
        let cy = y + off[0], cx = x + off[1];
        let selector = `.surface-cell[data-pos="${cy}-${cx}"]`;
        let c = document.querySelector(selector);
        if (c) {
            if (show) c.classList.add(ok ? "preview-ok" : "preview-bad");
            else { c.classList.remove("preview-ok"); c.classList.remove("preview-bad"); }
        }
    }
}
</script>
</body>
</html>
