<?php
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true) die();

use Bitrix\Main\Loader;
Loader::IncludeModule('highloadblock');
use Bitrix\Highloadblock as HL;

if(!class_exists('CurrencyCalculator')) {
    class CurrencyCalculator extends CBitrixComponent {

        private $id_list_currency;
        private $id_exchange_rates;
	private $list_currency;
	private $exchange_rates;

        function __construct($component = null)
        {
            parent::__construct($component);
        }

        function getEntityDataClass($HlBlockId)
	{
            if (empty($HlBlockId) || $HlBlockId < 1)
            {
                return false;
            }
            $hlblock = HL\HighloadBlockTable::getById($HlBlockId)->fetch();
            $entity = HL\HighloadBlockTable::compileEntity($hlblock);
            $entity_data_class = $entity->getDataClass();
            return $entity_data_class;
        }
		
	function getBlockTableList ()
	{
	    $dbItems = HL\HighloadBlockTable::getList();
            $list = [];

            while($arRes = $dbItems->fetch()){
                $list[$arRes["NAME"]] = $arRes["ID"];
            }
			
	    return $list;
	}
		
	function getIdHighloadblock()
	{
	    $list = $this->getBlockTableList();
			
	    if ( isset($list["ListCurrency"]) ) {
                $this->id_list_currency = $list["ListCurrency"];
            } else {
                $this->id_list_currency = $this->createListCurrency();
            }

            if ( isset($list["ExchangeRates"]) ) {
                $this->id_exchange_rates = $list["ExchangeRates"];
            } else {
                $this->id_exchange_rates = $this->createExchangeRates();
            }
	}
		
	function cbrXMLDailyRu()
	{
	    return json_decode(file_get_contents('https://www.cbr-xml-daily.ru/daily_json.js'), true);
	}
		
	function setRates()
	{
	    $ar_rates = $this->cbrXMLDailyRu();
	    $date = date('d.m.Y');
	    $entity_data_class = $this->getEntityDataClass($this->id_exchange_rates);

	    foreach($this->list_currency as $value){

	        $car_rate = $ar_rates["Valute"][$value["UF_SHORT_NAME"]]["Value"]??1;

	        $this->exchange_rates[$value["UF_SHORT_NAME"]] = [
		    'UF_VALUE' => $car_rate,
	        ];

	        $entity_data_class::add([
		    'UF_CURRENCY_ID' => $value["ID"],
		    'UF_VALUE' => $car_rate,
		    'UF_DATE' => $date,
	        ]);
	    }
	}
		
	function getRates()
	{
	    $entity_data_class = $this->getEntityDataClass($this->id_exchange_rates);
            $rsData = $entity_data_class::getList([
	       "select" => array("UF_CURRENCY_ID", "UF_VALUE"),
	       "order" => array("ID" => "ASC"),
	       "filter" => array("UF_DATE"=>date('d.m.Y'))  // Задаем параметры фильтра выборки
	    ]);
			
	    while($arData = $rsData->Fetch()){
	        $this->exchange_rates[$this->list_currency[$arData["UF_CURRENCY_ID"]]["UF_SHORT_NAME"]]["UF_VALUE"] = $arData["UF_VALUE"];
	    }
		
	    if (empty($this->exchange_rates)) {
	        $this->setRates();
	    }
			
	    return true;
	}
		
	function getCurrency()
	{
	    $entity_data_class = $this->getEntityDataClass($this->id_list_currency);
            $rsData = $entity_data_class::getList([
	       "select" => array("*")
	    ]);
			
	    while($arData = $rsData->Fetch()){
	        $this->list_currency[$arData["ID"]] = $arData;
	        $currency .= '{name:"'.$arData['UF_SHORT_NAME'].'", desc:"'.$arData['UF_CURRENCY_NAME'].'"},';
	    }
		
	    $this->arResult["LIST_CURRENCY"] = '['.$currency.']';
	}
		

        function createListCurrency()
        {
            $arLangs = [
                'ru' => 'Список валют',
                'en' => 'List Currency'
            ];

            $result = HL\HighloadBlockTable::add([
                'NAME' => 'ListCurrency',
                'TABLE_NAME' => 'list_currency',
            ]);

            if ($result->isSuccess()) {
                $id = $result->getId();
                foreach($arLangs as $lang_key => $lang_val){
                    HL\HighloadBlockLangTable::add([
                        'ID' => $id,
                        'LID' => $lang_key,
                        'NAME' => $lang_val
                    ]);
                }
            } else {
                $errors = $result->getErrorMessages();
                var_dump($errors);
            }

            $UFObject = 'HLBLOCK_'.$id;

            $arCurrencyFields = [
                'UF_CURRENCY_NAME'=>[
                    'ENTITY_ID' => $UFObject,
                    'FIELD_NAME' => 'UF_CURRENCY_NAME',
                    'USER_TYPE_ID' => 'string',
                    'MANDATORY' => 'Y',
                    "EDIT_FORM_LABEL" => ['ru'=>'Валюта', 'en'=>'Currency'],
                    "LIST_COLUMN_LABEL" => ['ru'=>'Валюта', 'en'=>'Currency'],
                    "LIST_FILTER_LABEL" => ['ru'=>'Валюта', 'en'=>'Currency'],
                    "ERROR_MESSAGE" => ['ru'=>'', 'en'=>''],
                    "HELP_MESSAGE" => ['ru'=>'', 'en'=>''],
                ],
                'UF_SHORT_NAME'=>[
                    'ENTITY_ID' => $UFObject,
                    'FIELD_NAME' => 'UF_SHORT_NAME',
                    'USER_TYPE_ID' => 'string',
                    'MANDATORY' => 'Y',
                    "EDIT_FORM_LABEL" => ['ru'=>'Короткое имя', 'en'=>'Short name'],
                    "LIST_COLUMN_LABEL" => ['ru'=>'Короткое имя', 'en'=>'Short name'],
                    "LIST_FILTER_LABEL" => ['ru'=>'Короткое имя', 'en'=>'Short name'],
                    "ERROR_MESSAGE" => ['ru'=>'', 'en'=>''],
                    "HELP_MESSAGE" => ['ru'=>'', 'en'=>''],
                ],
            ];

            foreach($arCurrencyFields as $arCurrencyField){
                $obUserField  = new CUserTypeEntity;
                $ID = $obUserField->Add($arCurrencyField);
            }

            $entity_data_class = $this->getEntityDataClass($id);
            $ListCurrency = [
                1 => [
                    'UF_CURRENCY_NAME' => 'Рубль',
                    'UF_SHORT_NAME' => 'RU',
                ],
                2 => [
                    'UF_CURRENCY_NAME' => 'Евро',
                    'UF_SHORT_NAME' => 'EUR',
                ],
                3 => [
                    'UF_CURRENCY_NAME' => 'Доллар',
                    'UF_SHORT_NAME' => 'USD',
                ],
            ];
            foreach ($ListCurrency as $value) {
                $result = $entity_data_class::add($value);
            }

            return $id;
        }

        function createExchangeRates()
        {
            $arLangs = Array(
                'ru' => 'Курсы валют',
                'en' => 'Exchange Rates'
            );

            $result = HL\HighloadBlockTable::add(array(
                'NAME' => 'ExchangeRates',
                'TABLE_NAME' => 'exchange_rates',
            ));

            if ($result->isSuccess()) {
                $id = $result->getId();
                foreach($arLangs as $lang_key => $lang_val){
                    HL\HighloadBlockLangTable::add(array(
                        'ID' => $id,
                        'LID' => $lang_key,
                        'NAME' => $lang_val
                    ));
                }
            } else {
                $errors = $result->getErrorMessages();
                var_dump($errors);
            }

            $UFObject = 'HLBLOCK_'.$id;

            $arRatesFields = [
                'UF_CURRENCY_ID'=>[
                    'ENTITY_ID' => $UFObject,
                    'FIELD_NAME' => 'UF_CURRENCY_ID',
                    'USER_TYPE_ID' => 'string',
                    'MANDATORY' => 'Y',
                    "EDIT_FORM_LABEL" => ['ru'=>'ID Валюты', 'en'=>'Currency ID'],
                    "LIST_COLUMN_LABEL" => ['ru'=>'ID Валюты', 'en'=>'Currency ID'],
                    "LIST_FILTER_LABEL" => ['ru'=>'ID Валюты', 'en'=>'Currency ID'],
                    "ERROR_MESSAGE" => ['ru'=>'', 'en'=>''],
                    "HELP_MESSAGE" => ['ru'=>'', 'en'=>''],
                ],
                'UF_VALUE'=>[
                    'ENTITY_ID' => $UFObject,
                    'FIELD_NAME' => 'UF_VALUE',
                    'USER_TYPE_ID' => 'string',
                    'MANDATORY' => 'Y',
                    "EDIT_FORM_LABEL" => ['ru'=>'Значение', 'en'=>'Value'],
                    "LIST_COLUMN_LABEL" => ['ru'=>'Значение', 'en'=>'Value'],
                    "LIST_FILTER_LABEL" => ['ru'=>'Значение', 'en'=>'Value'],
                    "ERROR_MESSAGE" => ['ru'=>'', 'en'=>''],
                    "HELP_MESSAGE" => ['ru'=>'', 'en'=>''],
                ],
				'UF_DATE'=>[
                    'ENTITY_ID' => $UFObject,
                    'FIELD_NAME' => 'UF_DATE',
                    'USER_TYPE_ID' => 'date',
                    'MANDATORY' => 'Y',
                    "EDIT_FORM_LABEL" => ['ru'=>'Дата', 'en'=>'Date'],
                    "LIST_COLUMN_LABEL" => ['ru'=>'Дата', 'en'=>'Date'],
                    "LIST_FILTER_LABEL" => ['ru'=>'Дата', 'en'=>'Date'],
                    "ERROR_MESSAGE" => ['ru'=>'', 'en'=>''],
                    "HELP_MESSAGE" => ['ru'=>'', 'en'=>''],
                ],
            ];

            foreach($arRatesFields as $arRatesField){
                $obUserField  = new CUserTypeEntity;
                $ID = $obUserField->Add($arRatesField);
            }

            return $id;
        }

        public function executeComponent()
        {
	    if ($this->StartResultCache(false, date('d.m.Y')))
	    {
	        $this->getIdHighloadblock();
	        $this->getCurrency();
	        $this->getRates();
	        $this->arResult["EXCHANGE_RATES"] = $this->exchange_rates;
	        $this->includeComponentTemplate(); 
	    }		
        }
    }
}
