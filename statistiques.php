<?php
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/fonctions.php';

$effectifs = [];
$erreur = null;

try {
    $stmt = $pdo->query("
        SELECT CASE
                   WHEN civilite = 'Monsieur' THEN 'Homme'
                   WHEN civilite IN ('Madame', 'Mademoiselle') THEN 'Femme'
                   ELSE 'Non renseigné'
               END AS sexe,
               COUNT(*) AS effectif
        FROM personne
        GROUP BY sexe
        ORDER BY FIELD(sexe, 'Homme', 'Femme', 'Non renseigné')
    ");
    $effectifs = $stmt->fetchAll();
} catch (PDOException $e) {
    $erreur = 'Impossible de charger les statistiques des étudiants.';
}

$labels = array_column($effectifs, 'sexe');
$valeurs = array_map('intval', array_column($effectifs, 'effectif'));
$totalEtudiants = array_sum($valeurs);

$base = '';
$titre = 'Statistiques';
require __DIR__ . '/includes/header.php';
?>

<style>
    .statistiques-intro {
        margin-bottom: 20px;
        color: var(--text-muted);
    }
    .statistiques-graphiques {
        display: grid;
        grid-template-columns: repeat(2, minmax(0, 1fr));
        gap: 20px;
        margin: 24px 0;
    }
    .statistiques-carte {
        min-width: 0;
        padding: 20px;
        background: var(--bg-card);
        border: 1px solid var(--border-light);
        border-radius: var(--radius-lg);
        box-shadow: var(--shadow-sm);
    }
    .statistiques-carte h3 {
        margin: 0 0 18px;
        color: var(--text-heading);
    }
    .statistiques-canvas {
        position: relative;
        height: 320px;
    }
    .statistiques-vide {
        color: var(--text-muted);
        text-align: center;
    }
    @media (max-width: 760px) {
        .statistiques-graphiques {
            grid-template-columns: 1fr;
        }
    }
</style>

<p class="statistiques-intro">
    Répartition calculée depuis les étudiants enregistrés. Le sexe est déduit de la civilité :
    Monsieur = Homme, Madame/Mademoiselle = Femme.
</p>

<?php if ($erreur): ?>
    <p class="erreur"><?= h($erreur) ?></p>
<?php else: ?>
    <p><strong>Total des étudiants :</strong> <?= h($totalEtudiants) ?></p>
    <div class="statistiques-graphiques">
        <section class="statistiques-carte">
            <h3>Nombre d’étudiants par sexe</h3>
            <?php if ($totalEtudiants > 0): ?>
                <div class="statistiques-canvas">
                    <canvas id="graphique-sexe-barres" aria-label="Histogramme du nombre d’étudiants par sexe"></canvas>
                </div>
            <?php else: ?>
                <p class="statistiques-vide">Aucun étudiant enregistré.</p>
            <?php endif; ?>
        </section>
        <section class="statistiques-carte">
            <h3>Pourcentage d’étudiants par sexe</h3>
            <?php if ($totalEtudiants > 0): ?>
                <div class="statistiques-canvas">
                    <canvas id="graphique-sexe-camembert" aria-label="Camembert du pourcentage d’étudiants par sexe"></canvas>
                </div>
            <?php else: ?>
                <p class="statistiques-vide">Aucun étudiant enregistré.</p>
            <?php endif; ?>
        </section>
    </div>

    <?php if ($totalEtudiants > 0): ?>
        <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.7/dist/chart.umd.min.js"></script>
        <script>
            (function () {
                var labels = <?= json_encode($labels, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;
                var valeurs = <?= json_encode($valeurs, JSON_NUMERIC_CHECK) ?>;
                var couleurs = {
                    Homme: '#2563eb',
                    Femme: '#db2777',
                    'Non renseigné': '#64748b'
                };
                var couleursGraphique = labels.map(function (label) {
                    return couleurs[label] || '#64748b';
                });
                var total = valeurs.reduce(function (somme, valeur) {
                    return somme + valeur;
                }, 0);

                var etiquettesValeurs = {
                    id: 'etiquettesValeurs',
                    afterDatasetsDraw: function (graphique) {
                        var contexte = graphique.ctx;
                        var elements = graphique.getDatasetMeta(0).data;
                        contexte.save();
                        contexte.font = '600 12px Arial';
                        contexte.textAlign = 'center';
                        contexte.textBaseline = 'middle';

                        elements.forEach(function (element, index) {
                            var position = element.tooltipPosition();
                            if (graphique.config.type === 'bar') {
                                contexte.fillStyle = '#111827';
                                contexte.fillText(String(valeurs[index]), position.x, position.y - 10);
                            } else {
                                var pourcentage = (valeurs[index] / total * 100).toFixed(1);
                                contexte.fillStyle = '#ffffff';
                                contexte.fillText(valeurs[index] + ' (' + pourcentage + '%)', position.x, position.y);
                            }
                        });

                        contexte.restore();
                    }
                };

                if (typeof Chart === 'undefined') {
                    document.querySelectorAll('.statistiques-carte').forEach(function (carte) {
                        var message = document.createElement('p');
                        message.className = 'erreur';
                        message.textContent = 'Impossible de charger la bibliothèque des graphiques.';
                        carte.appendChild(message);
                    });
                    return;
                }

                var donnees = {
                    labels: labels,
                    datasets: [{
                        label: 'Nombre d’étudiants',
                        data: valeurs,
                        backgroundColor: couleursGraphique,
                        borderColor: '#ffffff',
                        borderWidth: 2
                    }]
                };

                new Chart(document.getElementById('graphique-sexe-barres'), {
                    type: 'bar',
                    data: donnees,
                    plugins: [etiquettesValeurs],
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: {
                            title: {
                                display: true,
                                text: 'Effectif : ' + total + ' étudiant(s)'
                            },
                            legend: { display: true, position: 'bottom' }
                        },
                        scales: {
                            y: {
                                beginAtZero: true,
                                ticks: { precision: 0 },
                                title: { display: true, text: 'Nombre d’étudiants' }
                            },
                            x: { title: { display: true, text: 'Sexe' } }
                        }
                    }
                });

                new Chart(document.getElementById('graphique-sexe-camembert'), {
                    type: 'pie',
                    data: donnees,
                    plugins: [etiquettesValeurs],
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: {
                            title: {
                                display: true,
                                text: 'Répartition des étudiants par sexe'
                            },
                            legend: {
                                display: true,
                                position: 'bottom',
                                labels: {
                                    generateLabels: function (graphique) {
                                        return graphique.data.labels.map(function (label, index) {
                                            var pourcentage = (valeurs[index] / total * 100).toFixed(1);
                                            return {
                                                text: label + ' — ' + valeurs[index] + ' (' + pourcentage + '%)',
                                                fillStyle: couleursGraphique[index],
                                                strokeStyle: '#ffffff',
                                                lineWidth: 2,
                                                hidden: false,
                                                index: index
                                            };
                                        });
                                    }
                                }
                            },
                            tooltip: {
                                callbacks: {
                                    label: function (contexte) {
                                        var pourcentage = (contexte.raw / total * 100).toFixed(1);
                                        return contexte.label + ' : ' + contexte.raw + ' (' + pourcentage + '%)';
                                    }
                                }
                            }
                        }
                    }
                });
            })();
        </script>
    <?php endif; ?>
<?php endif; ?>

<?php require __DIR__ . '/includes/footer.php'; ?>
