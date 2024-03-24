<?php

    use Bitrix\Main\Loader;
    use Bitrix\Main\Application;
    use Bitrix\Main\Entity;

    // ����������� ������
    CAgent::AddAgent("ImportXMLAgent::checkXMLfiles();", "checkXMLfile", "N", 86400, "", "Y", "", 30);

    Loader::includeModule('iblock');

    class ImportXMLAgent {
        public static function checkXMLfiles() {
            $xmlFolder = $_SERVER['DOCUMENT_ROOT'] . '/���� �� �����/';
            $xmlFiles = glob($xmlFolder . '*.xml');
            foreach($xmlFiles as $xmlFile) {
                self::checkXMLfile($xmlFile);
            }
        }

        /**
         * @param $xmlFile // ��������� XML �����
         *
         * @return void
         */
        private static function checkXMLfile($xmlFile) {
            $xmlData = simplexml_load_file($xmlFile);
            if($xmlData === false) return;

            // ��������� ������ �� XML ����� � ���������� ������� ��������� ���������
            foreach($xmlData->Products->Product as $product) {
                $code = (string)$product->Code;
                $rest = (int)$product->Rest;
                self::checkElementProperty($code, $rest);
            }

            $doneFile = $xmlFile . '.done';
            rename($xmlFile, $doneFile);
        }

        /**
         * @param $code * ���������� ���
         * @param $rest * �������� � xml
         *
         * @return string
         */
        private static function checkElementProperty($code, $rest) {
            $IBLOCK_ID = 1;
            // �������� �������� ������� �������� �� �������� ����
            $res = CIBlockElement::GetList([], [
                'IBLOCK_ID' => $IBLOCK_ID,
                '=XML_ID' => $code,
                'ACTIVE' => 'Y', // ��������� ������ �������� ��������
            ], false, false, ['ID', 'IBLOCK_ID', 'NAME']);
            if($element = $res->Fetch()) {
                $ELEMENT_ID = $element['ID'];

                // �������� �������� �������� �� ��� ����������� ����
                $propertyCode = 'PROPERTY_CODE';
                $propertyValue = CIBlockElement::GetProperty($IBLOCK_ID, $ELEMENT_ID, [], ["CODE" => $propertyCode]);

                // ������������ �������� ��������
                if($prop = $propertyValue->Fetch()) {
                    CIBlockElement::SetPropertyValuesEx($ELEMENT_ID, $IBLOCK_ID, [$propertyCode => $rest]);
                    return "�������� �������� '{$propertyCode}' �������� �� '{$rest}'";
                } else {
                    return "�������� '{$propertyCode}' �� ������� ��� �������� '{$element['NAME']}'";
                }
            } else {
                return "������� � ������� ����� '{$code}' �� ������";
            }
        }
    }