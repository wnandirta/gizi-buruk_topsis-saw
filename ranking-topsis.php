<?php

require_once('includes/init.php');

$judul_page = 'Perankingan Menggunakan Metode TOPSIS';
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
 * Fetch (alternatif)
 * ------------------------------------------- */
$query2 = $pdo->prepare('SELECT id_balita, no_balita FROM balita');
$query2->execute();			
$query2->setFetchMode(PDO::FETCH_ASSOC);
$balitas = $query2->fetchAll();


/* Matrix Keputusan (X)
 * ------------------------------------------- */
$matriks_x = array();
foreach($kriterias as $kriteria):
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
	
	// Mencari akar dari penjumlahan kuadrat
	$jumlah_kuadrat = 0;
	foreach($nilai_balitas as $nilai_balita):
		$jumlah_kuadrat += pow($nilai_balita, 2);
	endforeach;
	$akar_kuadrat = sqrt($jumlah_kuadrat);
	
	// Mencari hasil bagi akar kuadrat
	// Lalu dimasukkan ke array $matriks_r
	foreach($nilai_balitas as $id_balita => $nilai_balita):
		$matriks_r[$id_kriteria][$id_balita] = $nilai_balita / $akar_kuadrat;
	endforeach;
	
endforeach;


/*  Matriks Y
 * ------------------------------------------- */
$matriks_y = array();
foreach($kriterias as $kriteria):
	foreach($balitas as $balita):
		
		$bobot = $kriteria['bobot'];
		$id_balita = $balita['id_balita'];
		$id_kriteria = $kriteria['id_kriteria'];
		
		$nilai_r = $matriks_r[$id_kriteria][$id_balita];
		$matriks_y[$id_kriteria][$id_balita] = $bobot * $nilai_r;

	endforeach;
endforeach;


/*  Solusi Ideal Positif & Negarif
 * ------------------------------------------- */
$solusi_ideal_positif = array();
$solusi_ideal_negatif = array();
foreach($kriterias as $kriteria):

	$id_kriteria = $kriteria['id_kriteria'];
	$type_kriteria = $kriteria['type'];
	
	$nilai_max = max($matriks_y[$id_kriteria]);
	$nilai_min = min($matriks_y[$id_kriteria]);
	
	if($type_kriteria == 'benefit'):
		$s_i_p = $nilai_max;
		$s_i_n = $nilai_min;
	elseif($type_kriteria == 'cost'):
		$s_i_p = $nilai_min;
		$s_i_n = $nilai_max;
	endif;
	
	$solusi_ideal_positif[$id_kriteria] = $s_i_p;
	$solusi_ideal_negatif[$id_kriteria] = $s_i_n;

endforeach;


/*  Jarak Ideal Positif & Negatif
 * ------------------------------------------- */
$jarak_ideal_positif = array();
$jarak_ideal_negatif = array();
foreach($balitas as $balita):

	$id_balita = $balita['id_balita'];		
	$jumlah_kuadrat_jip = 0;
	$jumlah_kuadrat_jin = 0;
	
	// Mencari penjumlahan kuadrat
	foreach($matriks_y as $id_kriteria => $nilai_balitas):
		
		$hsl_pengurangan_jip = $nilai_balitas[$id_balita] - $solusi_ideal_positif[$id_kriteria];
		$hsl_pengurangan_jin = $nilai_balitas[$id_balita] - $solusi_ideal_negatif[$id_kriteria];
		
		$jumlah_kuadrat_jip += pow($hsl_pengurangan_jip, 2);
		$jumlah_kuadrat_jin += pow($hsl_pengurangan_jin, 2);
	
	endforeach;
	
	// Mengakarkan hasil penjumlahan kuadrat
	$akar_kuadrat_jip = sqrt($jumlah_kuadrat_jip);
	$akar_kuadrat_jin = sqrt($jumlah_kuadrat_jin);
	
	// Memasukkan ke array matriks 
	$jarak_ideal_positif[$id_balita] = $akar_kuadrat_jip;
	$jarak_ideal_negatif[$id_balita] = $akar_kuadrat_jin;
	
endforeach;


/* Perangkingan
 * ------------------------------------------- */
$ranks = array();
foreach($balitas as $balita):

	$s_negatif = $jarak_ideal_negatif[$balita['id_balita']];
	$s_positif = $jarak_ideal_positif[$balita['id_balita']];	
	
	$nilai_v = $s_negatif / ($s_positif + $s_negatif);
	
	$ranks[$balita['id_balita']]['id_balita'] = $balita['id_balita'];
	$ranks[$balita['id_balita']]['no_balita'] = $balita['no_balita'];
	$ranks[$balita['id_balita']]['nilai'] = $nilai_v;
	
endforeach;
 
?>


<div class="main-content-row">
<div class="container clearfix">	

	<div class="main-content main-content-full the-content">
		
		<h1><?php echo $judul_page; ?></h1>
		
		
		
		
		<!--  Perangkingan  -->
		<?php		
		$sorted_ranks = $ranks;	
		
		// Sorting
		if(function_exists('array_multisort')):
			foreach ($sorted_ranks as $key => $row) {
				$no_balita[$key]  = $row['no_balita'];
				$nilai[$key] = $row['nilai'];
			}
			array_multisort($nilai, SORT_DESC, $no_balita, SORT_ASC, $sorted_ranks);
		endif;
		?>		
		<h3> Perangkingan (V) TOPSIS METHOD</h3>			
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

</div><!-- .container -->
</div><!-- .main-content-row -->

<?php
require_once('template-parts/footer.php');