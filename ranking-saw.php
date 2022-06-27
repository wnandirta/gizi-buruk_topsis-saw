<?php
require_once('includes/init.php');

$judul_page = 'Perankingan Menggunakan Metode SAW';
require_once('template-parts/header.php');

$digit = 4;

/* ---------------------------------------------
 * Fetch  kriteria
 * ------------------------------------------- */
$query = $pdo->prepare('SELECT id_kriteria, nama, type, bobot
	FROM kriteria ORDER BY urutan_order ASC');
$query->execute();
$query->setFetchMode(PDO::FETCH_ASSOC);
$kriterias = $query->fetchAll();

/* ---------------------------------------------
 * Fetch  (alternatif)
 * ------------------------------------------- */
$query2 = $pdo->prepare('SELECT id_balita, no_balita FROM balita');
$query2->execute();			
$query2->setFetchMode(PDO::FETCH_ASSOC);
$balitas = $query2->fetchAll();


/*  Matrix Keputusan (X)
 * ------------------------------------------- */
$matriks_x = array();
$list_kriteria = array();
foreach($kriterias as $kriteria):
	$list_kriteria[$kriteria['id_kriteria']] = $kriteria;
	foreach($balitas as $balita):
		
		$id_balita = $balita['id_balita'];
		$id_kriteria = $kriteria['id_kriteria'];
		
		// Fetch nilai dari db
		$query3 = $pdo->prepare('SELECT nilai FROM nilai_balita
			WHERE id_balita = :id_balita AND id_kriteria = :id_kriteria');
		$query3->execute(array(
			'id_balita' => $id_balita,
			'id_kriteria' => $id_kriteria,
		));			
		$query3->setFetchMode(PDO::FETCH_ASSOC);
		if($nilai_balita = $query3->fetch()) {
			// Jika ada nilai kriterianya
			$matriks_x[$id_kriteria][$id_balita] = $nilai_balita['nilai'];
		} else {			
			$matriks_x[$id_kriteria][$id_balita] = 0;
		}

	endforeach;
endforeach;

/*  Matriks Ternormalisasi (R)
 * ------------------------------------------- */
$matriks_r = array();
foreach($matriks_x as $id_kriteria => $nilai_balitas):
	
	$tipe = $list_kriteria[$id_kriteria]['type'];
	foreach($nilai_balitas as $id_alternatif => $nilai) {
		if($tipe == 'benefit') {
			$nilai_normal = $nilai / max($nilai_balitas);
		} elseif($tipe == 'cost') {
			$nilai_normal = min($nilai_balitas) / $nilai;
		}
		
		$matriks_r[$id_kriteria][$id_alternatif] = $nilai_normal;
	}
	
endforeach;


/* Perangkingan
 *  */
$ranks = array();
foreach($balitas as $balita):

	$total_nilai = 0;
	foreach($list_kriteria as $kriteria) {
	
		$bobot = $kriteria['bobot'];
		$id_balita = $balita['id_balita'];
		$id_kriteria = $kriteria['id_kriteria'];
		
		$nilai_r = $matriks_r[$id_kriteria][$id_balita];
		$total_nilai = $total_nilai + ($bobot * $nilai_r);

	}
	
	$ranks[$balita['id_balita']]['id_balita'] = $balita['id_balita'];
	$ranks[$balita['id_balita']]['no_balita'] = $balita['no_balita'];
	$ranks[$balita['id_balita']]['nilai'] = $total_nilai;
	
endforeach;
 
?>

<div class="main-content-row">
<div class="container clearfix">	

	<div class="main-content main-content-full the-content">
		
		<h1><?php echo $judul_page; ?></h1>
		
		
		<!--  Result -->
		<?php		
		$sorted_ranks = $ranks;		
		// Sorting 
		if(function_exists('array_multisort')):
			$no_balita = array();
			$nilai = array();
			foreach ($sorted_ranks as $key => $row) {
				$no_balita[$key]  = $row['no_balita'];
				$nilai[$key] = $row['nilai'];
			}
			array_multisort($nilai, SORT_DESC, $no_balita, SORT_ASC, $sorted_ranks);
		endif;
		?>		
		<h3> Perangkingan (V) SAW METHOD</h3>			
		<table class="pure-table pure-table-striped">
			<thead>					
				<tr>
					<th class="super-top-left">No. balita</th>
					<th>Ranking</th>
				</tr>
			</thead>
			<tbody>
				<?php foreach($sorted_ranks as $balita ): ?>
					<tr>
						<td><?php echo $balita['no_balita']; ?></td>
						<td><?php echo round($balita['nilai'], $digit); ?></td>											
					</tr>
				<?php endforeach; ?>
			</tbody>
		</table>			
		
	</div>

</div>
</div>

<?php
require_once('template-parts/footer.php');