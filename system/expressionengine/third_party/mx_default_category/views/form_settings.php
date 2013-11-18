<?php if($message) : ?>
<div class="mor alert success">
<p><?php print($message); ?></p>
</div>
<?php endif; ?>

<?php if($settings_form) : ?>
<?php echo form_open(
		'C=addons_extensions&M=extension_settings&file=&file=mx_default_category',
		'',
		array("file" => "mx_default_category")
	)
?>
<?php endif; ?>

<table class="mainTable padTable" id="event_table" border="0" cellpadding="0" cellspacing="0">
<tbody>
<tr>
<th><?=lang( 'channel' )?></th>
<th ><?=lang( 'default_category' )?></th>
</tr>
</tbody>

<?php 
	$out = "";
	$i = 1;
?>

<?php foreach($channel_data as $channel):?>
		<tr class="<?=print(($i&1) ? "odd" : "even")?>">	
		<td><strong><?=$channel->channel_title?></strong></td>
		<td>

		<?php if(isset($categories[$channel->channel_id])) : ?>	
		<?php foreach($categories[$channel->channel_id] as $key => $val):?>

		<?php if ( ! is_array($val))
		{
			$id = end(explode('group_id=', $val));
			$first = array(2 => $id);
		}
		else
		{
			$first = current($val);
		}
		?>
		
		<?php if (count($categories[$channel->channel_id]) > 1):?>
			<?=form_fieldset($key)?>
		<?php endif;?>
	
			<div id="cat_group_container_<?=$first[2]?>" class="cat_group_container">
		
				<?php if (is_array($val))
					foreach($val as $v):?>
					<label>
						<?=repeater(NBS.NBS.NBS.NBS, $v[5] - 1)?>
						<?=form_checkbox($input_prefix.'['.$channel->channel_id.'][' . $v[0] . ']', $v[0], ((isset($settings[$channel->channel_id][$v[0]])) ? TRUE : FALSE )).NBS.NBS.$v[1]?>
					</label>
				<?php endforeach;?>
		
			</div>
		
		<?php if (count($categories[$channel->channel_id]) > 1):?>
			<?=form_fieldset_close()?>
		<?php endif;?>
		
		<?php endforeach;?>
		<?php endif; ?>

		</td>
		</tr>

<?php $i++;?>

<?php endforeach;?>






</tbody>


</table>
<p class="centerSubmit"><input name="edit_field_group_name" value="<?=lang('save_extension_settings')?>" class="submit" type="submit"></p>

<?php echo form_close(); ?>