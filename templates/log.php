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
			<th>Event</th>
			<th>Translation(s)</th>
			<th>Validator</th>
			<th>Time</th>
			<th>Project</th>
			<th>Translation Set</th>
		</tr>
	</thead>
	<tbody>
	<?php foreach ( $warnings as $warning ) :  ?>
		<tr>
			<td><?php echo $warning->event; ?></td>
			<td><?php //echo esc_html( $warning->translation ); ?></td>
			<td><?php echo $warning->user_id; ?></td>
			<td><?php echo $warning->date_added; ?></td>
			<td><?php //echo $warning->project; ?></td>
			<td><?php echo $warning->translation_set_id; ?></td>
		</tr>
	<?php endforeach; ?>
	</tbody>
</table>
<?php gp_tmpl_footer();
