<?php require_once('includes/init.php'); ?>
<?php cek_login($role = array(1, 2)); ?>

<?php
$ada_error = false;
$result = '';

$id_balita = (isset($_GET['id'])) ? trim($_GET['id']) : '';

if(!$id_balita) {
	$ada_error = 'Maaf, data tidak dapat diproses.';
} else {
	$query = $pdo->prepare('SELECT id_balita FROM balita WHERE id_balita = :id_balita');
	$query->execute(array('id_balita' => $id_balita));
	$result = $query->fetch();
	
	if(empty($result)) {
		$ada_error = 'Maaf, data tidak dapat diproses.';
	} else {
		
		$handle = $pdo->prepare('DELETE FROM nilai_balita WHERE id_balita = :id_balita');				
		$handle->execute(array(
			'id_balita' => $result['id_balita']
		));
		$handle = $pdo->prepare('DELETE FROM balita WHERE id_balita = :id_balita');				
		$handle->execute(array(
			'id_balita' => $result['id_balita']
		));
		redirect_to('list-balita.php?status=sukses-hapus');
		
	}
}
?>

<?php
$judul_page = 'Hapus balita';
require_once('template-parts/header.php');
?>

	<div class="main-content-row">
	<div class="container clearfix">
	
		<?php include_once('template-parts/sidebar-balita.php'); ?>
	
		<div class="main-content the-content">
			<h1><?php echo $judul_page; ?></h1>
			
			<?php if($ada_error): ?>
			
				<?php echo '<p>'.$ada_error.'</p>'; ?>	
			
			<?php endif; ?>
			
		</div>
	
	</div><!-- .container -->
	</div><!-- .main-content-row -->


<?php
require_once('template-parts/footer.php');