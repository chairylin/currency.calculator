<?php
if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true){
	die();
}
$this->addExternalJS("/local/components/custom/currency.calculator/vue.min.js");
?>

<style>
	#databinding{
		padding: 20px 15px 15px 15px;
		margin: 0 0 25px 0;
		width: auto;
		background-color: #e7e7e7;
	}
	span, option, input {
		font-size:25px;
	}
	.default {
		margin-top: 10px;
		font-size: 12px;
	}
</style>
      
	<div id = "databinding" style = "">
		<h1>Валютный калькулятор</h1>
		<span>Обменять:</span><input type = "number" v-model.number = "amount" placeholder = "Обменять" /><br/><br/>
		<span>Из:</span>
		<select v-model = "convertfrom" style = "width:300px;font-size:25px;">
		<option v-for = "(a, index) in currencyfrom"  v-bind:value = "a.name">{{a.desc}}</option>
		</select>
		<span>в:</span>
		<select v-model = "convertto" style = "width:300px;font-size:25px;">
		<option v-for = "(a, index) in currencyfrom" v-bind:value = "a.name">{{a.desc}}</option>
		</select><br/><br/>
		<span> {{amount}} {{convertfrom}} = {{finalamount.summ}} {{convertto}}</span>
		<br/><br/>
		<span class="default"> 1 {{convertfrom}} = {{finalamount.rate}} {{convertto}}</span>
	</div>
      
<script type = "text/javascript">

	var rates = JSON.parse('<?=json_encode($arResult["EXCHANGE_RATES"])?>');
	var list_currency = <?=$arResult["LIST_CURRENCY"]?>;
	
	var vm = new Vue({
		el: '#databinding',
		data: {
		   name:'',
		   currencyfrom:list_currency,
		   convertfrom:"USD",
		   convertto:"RU",
		   amount:"1000",
		},
		computed :{
		   finalamount:function() {
			  var to = this.convertto;
			  var from = this.convertfrom;
			  var rate = rates[from].UF_VALUE / rates[to].UF_VALUE;
			  var final;
			  
			  let summ = (this.amount * rate).toFixed(2);
			  final = {
				  summ: summ,
				  rate: rate.toFixed(4),
			  };
			  
			  return final;
		   }
		}
	});
</script>