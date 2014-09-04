{capture assign="pay_quartely_row"}<tr class="crm-membership-type-form-block-pay_quartely"><td class="label">{$form.pay_quartely.label}</td><td>{$form.pay_quartely.html}<br><span class="description">{ts}Only applicable for yearly membership{/ts}</span></td></tr>{/capture}

{* reposition the above block after period field *}
<script type="text/javascript">
{literal}
cj(function() {
  cj('tr.crm-membership-type-form-block-duration_unit_interval').after('{/literal}{$pay_quartely_row}{literal}');
}); 
{/literal}
</script>