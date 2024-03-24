<?php

    use Bitrix\Main\Loader;
    use Bitrix\Main\Application;
    use Bitrix\Main\Entity;

    // Регистрация агента
    CAgent::AddAgent("ImportXMLAgent::checkXMLfiles();", "checkXMLfile", "N", 86400, "", "Y", "", 30);

    Loader::includeModule('iblock');

    class ImportXMLAgent {
        public static function checkXMLfiles() {
            $xmlFolder = $_SERVER['DOCUMENT_ROOT'] . '/путь до файла/';
            $xmlFiles = glob($xmlFolder . '*.xml');
            foreach($xmlFiles as $xmlFile) {
                self::checkXMLfile($xmlFile);
            }
        }

        /**
         * @param $xmlFile // Обработка XML файла
         *
         * @return void
         */
        private static function checkXMLfile($xmlFile) {
            $xmlData = simplexml_load_file($xmlFile);
            if($xmlData === false) return;

            // Обработка данных из XML файла и обновление свойств элементов инфоблока
            foreach($xmlData->Products->Product as $product) {
                $code = (string)$product->Code;
                $rest = (int)$product->Rest;
                self::checkElementProperty($code, $rest);
            }

            $doneFile = $xmlFile . '.done';
            rename($xmlFile, $doneFile);
        }

        /**
         * @param $code * Символьный код
         * @param $rest * Значение с xml
         *
         * @return string
         */
        private static function checkElementProperty($code, $rest) {
            $IBLOCK_ID = 1;
            // Получаем значения свойств элемента по внешнему коду
            $res = CIBlockElement::GetList([], [
                'IBLOCK_ID' => $IBLOCK_ID,
                '=XML_ID' => $code,
                'ACTIVE' => 'Y', // Учитываем только активные элементы
            ], false, false, ['ID', 'IBLOCK_ID', 'NAME']);
            if($element = $res->Fetch()) {
                $ELEMENT_ID = $element['ID'];

                // Получаем значение свойства по его символьному коду
                $propertyCode = 'PROPERTY_CODE';
                $propertyValue = CIBlockElement::GetProperty($IBLOCK_ID, $ELEMENT_ID, [], ["CODE" => $propertyCode]);

                // Обрабатываем значения свойства
                if($prop = $propertyValue->Fetch()) {
                    CIBlockElement::SetPropertyValuesEx($ELEMENT_ID, $IBLOCK_ID, [$propertyCode => $rest]);
                    return "Значение свойства '{$propertyCode}' изменено на '{$rest}'";
                } else {
                    return "Свойство '{$propertyCode}' не найдено для элемента '{$element['NAME']}'";
                }
            } else {
                return "Элемент с внешним кодом '{$code}' не найден";
            }
        }
    }