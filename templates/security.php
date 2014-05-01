<?php
gp_title( sprintf( __('%s &lt; GlotPress'), __('Security') ) );
gp_breadcrumb( array(
	__('Security log'),
) );
gp_tmpl_header();
?>
<table class="translation-sets">
	<thead>
		<tr>
			<th>Discarded Warning</th>
			<th>Translation(s)</th>
			<th>Validator</th>
			<th>Time</th>
			<th>Project</th>
			<th>Translation Set</th>
		</tr>
	</thead>
	<tbody>
	<?php foreach ( $warnings as $warning ) :  ?>
		<?php gp_error_log_dump($warning); ?>
		<tr>
			<td><?php echo $warning->tag; ?></td>
			<td><?php echo esc_html( $warning->translation ); ?></td>
			<td><?php echo $warning->user; ?></td>
			<td><?php echo $warning->time; ?></td>
			<td><?php echo $warning->project; ?></td>
			<td><?php echo $warning->translation_set; ?></td>
		</tr>
	<?php endforeach; ?>
	</tbody>
</table>
<?php gp_tmpl_footer();
