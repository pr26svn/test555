<?
$_SERVER["DOCUMENT_ROOT"] = "/home/hosting/www"; //��� ����� � ������ �� ��������� �������� ��������
$DOCUMENT_ROOT = $_SERVER["DOCUMENT_ROOT"];

define("NO_KEEP_STATISTIC", true);
define("NOT_CHECK_PERMISSIONS", true);

require($_SERVER["DOCUMENT_ROOT"] . "/bitrix/modules/main/include/prolog_before.php");
set_time_limit(0);
CModule::IncludeModule("sale");

//������� ��������� ��������

function getUserDelay($userId)
{
    // �������� ������ �������
    $arBasketItems = array();
    $dbBasketItems = CSaleBasket::GetList(
        array(
            "NAME" => "ASC",
            "ID" => "ASC"
        ),
        array(
            "FUSER_ID" => $userId,
            "LID" => SITE_ID,
            "ORDER_ID" => "NULL"
        ),
        false,
        false,
        array("ID", "CALLBACK_FUNC", "MODULE", "NAME",
            "PRODUCT_ID", "QUANTITY", "DELAY",
            "CAN_BUY", "PRICE", "DATE_INSERT", "DATE_UPDATE")
    );
    while ($arItems = $dbBasketItems->Fetch()) {
        if (strlen($arItems["CALLBACK_FUNC"]) > 0) {
            CSaleBasket::UpdatePrice($arItems["ID"],
                $arItems["CALLBACK_FUNC"],
                $arItems["MODULE"],
                $arItems["PRODUCT_ID"],
                $arItems["QUANTITY"]);
            $arItems = CSaleBasket::GetByID($arItems["ID"]);
        }

        $arBasketItems[] = $arItems;
    }

    // �������� ������, ���������� ���������� �� ������� ������ �������


    $whishlist = "";
    foreach ($arBasketItems as $delay) {
        $notBuy = "Y"; // ���� ��� �������� ��������� �� ������ �����,
        $difference = floor(intval(abs(time() - strtotime($delay[DATE_INSERT]))) / (3600 * 24));
        // ���������� ���������� ��������, ���� ������� ����� 30 ����
        if ($delay["DELAY"] == "Y" && $difference < 30) {
            foreach ($arBasketItems as $notDelay) {
                //���������� ��������� �������� � ���������� � �����������
                if ($notDelay["DELAY"] != "Y" && $delay[PRODUCT_ID] == $notDelay[PRODUCT_ID] && $difference < 30) {
                    $notBuy = "N";  // ����� ��� ���������
                }
            }
            if ($notBuy == "Y") {
                $whishlist .= ' ' . $delay["NAME"] . ',';
            }
        }
    }
    $whishlist = trim($whishlist, ",");
    return $whishlist;
}


$dbUserBasketItems = CSaleBasket::GetList(
    array(
        "FUSER_ID" => "ASC"

    ),
    array(
        "LID" => SITE_ID,
        "ORDER_ID" => "NULL",
        "DELAY" => "Y"
    ),
    array("FUSER_ID"),
    false,
    array("FUSER_ID")
);
//�������� ���� ������������� � ������� ���� ���������� ������
while ($rr = $dbUserBasketItems->fetch()) {

    if ($ar = CSaleOrderUserProps::GetByID($rr['FUSER_ID'])) {
        $name = $ar['NAME']; //����� ��� ������������ �� �������


        $wishlist = getUserDelay($rr['FUSER_ID']);
        if ($wishlist) {
//���������� ������
            $arEventFields = array(
                "WISHLIST" => $wishlist,
                "USER" => $name,

            );
            CEvent::SendImmediate("SALE_NEW_ORDER", s1, $arEventFields, "N", 22); // ������� ������ � ��� ������� ����� ������� � ��������� �������� ������
            /*---------------*/
        }
    }

}
