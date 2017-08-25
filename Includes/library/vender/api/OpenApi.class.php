<?php
namespace Api;
class OpenApi{
  public function requestXML($type,$parms,$url="https://Intlflightapi.ctrip.com/Intlflightapi/OpenAPI.asmx?WSDL"){

  	    try{
		  		$client = new \SoapClient($url);
		  		 // $client = new \Org\Api\SoapClient($url);
		  		switch($type){
		  			 case 63:
		  			 case 64:
		  			 case 65:
			  		 case 60:
			  		   $res = $client->Handle(new Api($type,$parms));
			  		   return preg_replace("/utf-16/", "utf-8", $this->gzdecode(base64_decode($res->HandleResult)));
			  		 break;
			  		 default: 
			  		   $res = $client->ProcessRequest(new Api($type,$parms));
			  		   return preg_replace("/utf-16/", "utf-8", $this->gzdecode(base64_decode($res->ProcessRequestResult)));
			  		 break;
		  		}
	  	}catch(SOAPFault $e){
	  		dump($e);
	  	}
  }
  
  private function gzdecode($data) {
  	$flags = ord(substr($data, 3, 1));
  	$headerlen = 10;
  	$extralen = 0;
  	$filenamelen = 0;
  	if ($flags & 4) {
  		$extralen = unpack('v' ,substr($data, 10, 2));
  		$extralen = $extralen[1];
  		$headerlen += 2 + $extralen;
  	}
  	if ($flags & 8) // Filename
  		$headerlen = strpos($data, chr(0), $headerlen) + 1;
  	if ($flags & 16) // Comment
  		$headerlen = strpos($data, chr(0), $headerlen) + 1;
  	if ($flags & 2) // CRC at end of file
  		$headerlen += 2;
  	$unpacked = @gzinflate(substr($data, $headerlen));
  	if ($unpacked === FALSE)
  		$unpacked = $data;
  	return $unpacked;
  }
}

class api{
	// 199
	// public $userId=165;
	// public $password="98BB1CDAA88B7BA3801A2995BB7E7737";

	// 205
	public $userId=348;
	public $password="57B3B24DAAC4D0BDFE1D293160BE6B42";
	public $apiType=0;
	public $gzipRequestBytes="";
	public $setParmsKey=array();
	public $requestXML="";

	function __construct($apiType=0,$arr){
		$this->setParmsKey=$arr;
		switch($apiType){
			case 1://私有单程运价新增XML
				$xml = '<?xml version="1.0"?>
						<OwFareRequest xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xmlns:xsd="http://www.w3.org/2001/XMLSchema">
							<OwFareInfoList>';
					foreach ($this->setParmsKey as $k => $v){
					$xml .= '<OwFareInfo 
						              FareId="" 
						              PolicyId="'.$v['PolicyId'].'" 
						              ProductType="1" 
						              IsApplication="0" 
						              StockType="0" 
						              Owner="'.$v['Owner'].'" 
						              IsAirport="'.$v['IsAirport'].'" 
						              DepartCity="'.$v['DepartCity'].'" 
						              ArriveCity="'.$v['ArriveCity'].'" 
						              Routing="'.$v['Routing'].'" 
						              RoutingClass="'.$v['RoutingClass'].'" 
						              FareBasis="'.$v['FareBasis'].'"
						              ForbiddenFlight="'.$v['ForbiddenFlight'].'" 
						              Flight="'.$v['Flight'].'" 
						              IsAllowOpenJaw="'.$v['IsAllowOpenJaw'].'" 
						              OpenJawType="'.$v['OpenJawType'].'" 
						              IsAllowStopoverOutbound="'.$v['IsAllowStopoverOutbound'].'" 
						              OutboundDayTime="'.$v['OutboundDayTime'].'" 
						              OutboundDayTimeIndicator="'.(int)$v['OutboundDayTimeIndicator'].'" 
						              PuOutboundTravelDate="'.$v['PuOutboundTravelDate'].'"  
						              PuOutboundTravelDateIndicator="'.$v['PuOutboundTravelDateIndicator'].'"  
						              FCOutboundTravelDate="'.$v['FcOutboundTravelDate'].'" 
						              OutboundTravelDateExcept="'.$v['OutboundTravelDateExcept'].'" 
						              FCOutboundTravelDateIndicator="'.(int)$v['FcOutboundTravelDateIndicator'].'" 
						              SalesDate="'.$v['SalesDate'].'" 
						              Eligibility="'.(int)$v['Eligibility'].'" 
						              MinStay="'.$v['MinStay'].'" 
						              MaxStay="'.$v['MaxStay'].'" 
						              MinPax="'.$v['MinPax'].'" 
						              MaxPax="'.$v['MaxPax'].'" 
						              FareType="'.$v['FareType'].'" 
						              TicketType="'.(int)$v['TicketType'].'" 
						              SalesPrice="'.$v['SalesPrice'].'" 
						              Currency="'.$v['Currency'].'" 
						              AdultTax="'.$v['AdultTax'].'" 
						              ChdDiscoundRate="'.$v['ChdDiscoundRate'].'" 
						              ChdTax="'.$v['ChdTax'].'" 
						              InfantRate="'.$v['InfantRate'].'" 
						              InfantTax="'.$v['InfantTax'].'" 
						              Commition="'.$v['Commition'].'" 
						              Profit="'.$v['Profit'].'" 
						              TicketingDeadline="'.$v['TicketingDeadline'].'" 
						              IsNeedPnr="'.$v['IsNeedPnr'].'" 
						              InvoiceType="'.(int)$v['InvoiceType'].'" 
						              Nationality="'.$v['Nationality'].'" 
						              ExcludeNationality="'.$v['ExcludeNationality'].'" 
						              Age="'.$v['Age'].'" 
						              IsOutboundChangeable="'.$v['IsOutboundChangeable'].'" 
						              OutboundRebooingFee="'.$v['OutboundRebooingFee'].'" 
						              OutboundRebookingCurrency="'.$v['OutboundRebookingCurrency'].'" 
						              IsRefundable="'.$v['IsRefundable'].'" 
						              CancellationFee="'.$v['CancellationFee'].'"
						              CancellationCurrency="'.$v['CancellationCurrency'].'"
						              IsNoshowRevalidation="'.$v['IsNoshowRevalidation'].'" 
						              RevNoshowCondition="'.$v['RevNoshowCondition'].'"
						              RevNoshowOutFee="'.$v['RevNoshowOutFee'].'"
						              RevNoshowOutCurrency="'.$v['RevNoshowOutCurrency'].'"
						              IsNoshowRefund="'.$v['IsNoshowRefund'].'"
						              RefNoshowCondition="'.$v['RefNoshowCondition'].'"
						              RefNoshowFee="'.$v['RefNoshowFee'].'"
						              RefNoshowCurrency="'.$v['RefNoshowCurrency'].'"		 
						              YourOfficeNo="'.$v['YourOfficeNo'].'" 
						              TicketIngRemark="'.$v['TicketIngRemark'].'" 
						              WorkingTime="'.$v['WorkingTime'].'" 
						              OutboundBaggage="'.$v['OutboundBaggage'].'" 
						              ExternalID="'.$v['ExternalID'].'" 
						              AgentId="" />';
						       }
							$xml .= '</OwFareInfoList>
						</OwFareRequest>';
				break;
			case 2://私有单程运价删除XML
				$xml = '<?xml version="1.0"?>
						<OwFareRequest xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xmlns:xsd="http://www.w3.org/2001/XMLSchema">
							<OwFareInfoList>';
							foreach ($this->setParmsKey as $k => $v) {
								$xml .='<OwFareInfo FareID="'.$v['FareId'].'"/>';
							}
							$xml .='</OwFareInfoList>
						</OwFareRequest>';

				break;
			case 3://私有往返运价新增XML
				$xml = '<?xml version="1.0"?>
						<RtFareRequest xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xmlns:xsd="http://www.w3.org/2001/XMLSchema">
							<RtFareInfoList>';
							/* 0=>IsAllowOpenJaw 
								 0=>OpenJawType
								 add CombinablePolicyId
							*/
								foreach ($this->setParmsKey as $k => $v){
					$xml .= '<RtFareInfo
						              FareId=""
						              PolicyId="'.$v['PolicyId'].'"
						              ProductType="1"
						              IsApplication="0"
						              StockType="0"
						              Owner="'.$v['Owner'].'"
						              IsAirport="'.$v['IsAirport'].'"
						              DepartCity="'.$v['DepartCity'].'"
						              ArriveCity="'.$v['ArriveCity'].'"
						              IsOther="'.$v['IsOther'].'"
						              Routing="'.$v['Routing'].'"
						              RoutingClass="'.$v['RoutingClass'].'"
						              FareBasis="'.$v['FareBasis'].'"
						              ForbiddenFlight="'.$v['ForbiddenFlight'].'"
						              Flight="'.$v['Flight'].'"
						              IsAllowCombination="'.$v['IsAllowCombination'].'"
						              CombinablePolicyId="'.$v['CombinablePolicyId'].'"
						              IsAllowOpenJaw="'.$v['IsAllowOpenJaw'].'"
						              OpenJawType="'.$v['OpenJawType'].'"
						              IsAllowStopoverOutbound="'.$v['IsAllowStopoverOutbound'].'"
						              IsAllowStopoverInbound="'.$v['IsAllowStopoverInbound'].'"
						              OutboundDayTime="'.$v['OutboundDayTime'].'"
						              InboundDayTime="'.$v['InboundDayTime'].'"		
						              OutboundDayTimeIndicator="'.(int)$v['OutboundDayTimeIndicator'].'"
						              InboundDayTimeIndicator="'.(int)$v['InboundDayTimeIndicator'].'"
						              PuOutboundTravelDate="'.$v['PuOutboundTravelDate'].'"
						              PuInboundTravelDate="'.$v['PuInboundTravelDate'].'"
						              PuOutboundTravelDateIndicator="'.$v['PuOutboundTravelDateIndicator'].'"
						              PuInboundTravelDateIndicator="'.$v['PuInboundTravelDateIndicator'].'"
						              FcOutboundTravelDate="'.$v['FcOutboundTravelDate'].'"
						              FcInboundTravelDate="'.$v['FcInboundTravelDate'].'"
						              OutboundTravelDateExcept="'.$v['OutboundTravelDateExcept'].'"
						              InboundTravelDateExcept="'.$v['InboundTravelDateExcept'].'"		
						              FcOutboundTravelDateIndicator="'.(int)$v['FcOutboundTravelDateIndicator'].'"
						              FcInboundTravelDateIndicator="'.(int)$v['FcInboundTravelDateIndicator'].'"
						              SalesDate="'.$v['SalesDate'].'"
						              CommissionCalculation="'.$v['CommissionCalculation'].'"
						              Eligibility="'.(int)$v['Eligibility'].'"
						              MinStay="'.$v['MinStay'].'"
						              MaxStay="'.$v['MaxStay'].'"
						              MinPax="'.$v['MinPax'].'"
						              MaxPax="'.$v['MaxPax'].'"
						              FareType="'.$v['FareType'].'"
						              TicketType="'.(int)$v['TicketType'].'"
						              SalesPrice="'.$v['SalesPrice'].'"
						              Currency="'.$v['Currency'].'"
						              AdultTax="'.$v['AdultTax'].'"
						              ChdDiscoundRate="'.$v['ChdDiscoundRate'].'"
						              ChdTax="'.$v['ChdTax'].'"
						              InfantRate="'.$v['InfantRate'].'"
						              InfantTax="'.$v['InfantTax'].'"
						              Commition="'.$v['Commition'].'"
						              Profit="'.$v['Profit'].'"
						              TicketingDeadline="'.$v['TicketingDeadline'].'"
						              IsNeedPnr="'.$v['IsNeedPnr'].'"
						              InvoiceType="'.(int)$v['InvoiceType'].'"
						              Nationality="'.$v['Nationality'].'"
						              ExcludeNationality="'.$v['ExcludeNationality'].'"
						              Age="'.$v['Age'].'"
						              IsOutboundChangeable="'.$v['IsOutboundChangeable'].'"
						              OutboundRebooingFee="'.$v['OutboundRebooingFee'].'"
						              OutboundRebookingCurrency="'.$v['OutboundRebookingCurrency'].'"
						              IsInboundChangeable="'.$v['IsInboundChangeable'].'"
						              InboundRebooingFee="'.$v['InboundRebooingFee'].'"
						              InboundRebookingCurrency="'.$v['InboundRebookingCurrency'].'"
						              IsRefundable="'.$v['IsRefundable'].'"
						              IsPartlyUsedRefundable="'.$v['IsPartlyUsedRefundable'].'"
						              CancellationFee="'.$v['CancellationFee'].'"
						              CancellationCurrency="'.$v['CancellationCurrency'].'"
						              PartlyUsedRefundFee="'.$v['PartlyUsedRefundFee'].'"
						              PartlyUsedRefundCurrency="'.$v['PartlyUsedRefundCurrency'].'"
						              IsNoshowRevalidation="'.$v['IsNoshowRevalidation'].'"
						              RevNoshowCondition="'.$v['RevNoshowCondition'].'"
						              RevNoshowOutFee="'.$v['RevNoshowOutFee'].'"
						              RevNoshowOutCurrency="'.$v['RevNoshowOutCurrency'].'"
						              RevNoshowInFee="'.$v['RevNoshowInFee'].'"
						              RevNoshowInCurrency="'.$v['RevNoshowInCurrency'].'"
						              IsNoshowRefund="'.$v['IsNoshowRefund'].'"
						              RefNoshowCondition="'.$v['RefNoshowCondition'].'"
						              RefNoshowFee="'.$v['RefNoshowFee'].'"		
						              RefNoshowCurrency="'.$v['RefNoshowCurrency'].'"
						              RefNoshowPartlyFee="'.$v['RefNoshowPartlyFee'].'"		
						              RefNoshowPartlyCurrency="'.$v['RefNoshowPartlyCurrency'].'"
						              YourOfficeNo="'.$v['YourOfficeNo'].'"
						              TicketIngRemark="'.$v['TicketIngRemark'].'"
						              WorkingTime="'.$v['WorkingTime'].'"
						              OutboundBaggage="'.$v['OutboundBaggage'].'"
						              InboundBaggage="'.$v['InboundBaggage'].'"		
						              ExternalID="'.$v['ExternalID'].'"
						              AgentId="" />';
						      }
				$xml .= '</RtFareInfoList>
						</RtFareRequest>'; 
						
				break;
			case 4://私有往返运价删除XML
				$xml = '<?xml version="1.0"?>
						<RtFareRequest xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xmlns:xsd="http://www.w3.org/2001/XMLSchema">
							<RtFareInfoList>';
							foreach ($this->setParmsKey as $k => $v) {
								$xml .= '<RtFareInfo FareId="'.$v['FareId'].'" />';
							}
							$xml .= '</RtFareInfoList>
						</RtFareRequest>';
				break;
			case 5://私有单程运价修改XML
				$xml = '<?xml version="1.0"?>
						<OwFareRequest xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xmlns:xsd="http://www.w3.org/2001/XMLSchema">
							<OwFareInfoList>';
							foreach( $this->setParmsKey as $k => $v){
							$xml .=	'<OwFareInfo
						              FareId="'.$v['FareId'].'"
						              SalesDate="'.$v['SalesDate'].'"
						              SalesPrice="'.$v['SalesPrice'].'"
						              Commition="'.$v['Commition'].'"
						              Profit="'.$v['Profit'].'"
						              FCOutboundTravelDate="'.$v['FcOutboundTravelDate'].'"
						              FCInboundTravelDate="'.$v['FcInboundTravelDate'].'"
						              ExternalID="'.$v['ExternalID'].'"
						               />';
						  }
							$xml .= '</OwFareInfoList>
						</OwFareRequest>';

				break;
			case 6://私有单程运价查询XML
				$xml = '<?xml version="1.0"?>
                        <OwFareRequest xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xmlns:xsd="http://www.w3.org/2001/XMLSchema">
							  <OwFareInfoList>
							    <OwFareInfo 
						             FareID="'.$this->setParmsKey['FareID'].'"
						             PolicyID="'.$this->setParmsKey['PolicyId'].'"
						             IsActivated="'.$this->setParmsKey['IsActivated'].'" 
						             ProductType="1" 
						             Owner="'.$this->setParmsKey['Owner'].'" 
						             DepartCity="'.$this->setParmsKey['DepartCity'].'" 
						             ArriveCity="'.$this->setParmsKey['ArriveCity'].'" 
						             RoutingClass="'.$this->setParmsKey['RoutingClass'].'"
						             CreateUser="'.$this->setParmsKey['CreateUser'].'"
						             ExternalID="'.$this->setParmsKey['ExternalID'].'" 
						        />
							  </OwFareInfoList>
						</OwFareRequest>';
				break;
			case 7://私有往返运价修改XML
			  // 可以再添加原新增字段
				$xml = '<?xml version="1.0"?>
						<RtFareRequest xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xmlns:xsd="http://www.w3.org/2001/XMLSchema">
							<RtFareInfoList>';
								foreach ($this->setParmsKey as $k => $v) {
								$xml .= '<RtFareInfo
						              FareId="'.$v['FareId'].'"
						              SalesDate="'.$v['SalesDate'].'"
						              SalesPrice="'.$v['SalesPrice'].'"
						              Commition="'.$v['Commition'].'"
						              Profit="'.$v['Profit'].'"
						              FCOutboundTravelDate="'.$v['FcOutboundTravelDate'].'"
						              FCInboundTravelDate="'.$v['FcInboundTravelDate'].'"
						              ExternalID="'.$v['ExternalID'].'"
						               />';
						    }
							$xml .= '</RtFareInfoList>
						</RtFareRequest>';

				break;		
			case 8://私有往返运价查询XML
				$xml = '<?xml version="1.0"?>
                        <RtFareRequest xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xmlns:xsd="http://www.w3.org/2001/XMLSchema">
							  <RtFareInfoList>
							    <RtFareInfo 
						             FareId="'.$this->setParmsKey['FareId'].'"
						             PolicyID="'.$this->setParmsKey['PolicyId'].'"
						             IsActivated="'.$this->setParmsKey['IsActivated'].'" 
						             ProductType="1" 
						             Owner="'.$this->setParmsKey['Owner'].'" 
						             DepartCity="'.$this->setParmsKey['DepartCity'].'" 
						             ArriveCity="'.$this->setParmsKey['ArriveCity'].'" 
						             RoutingClass="'.$this->setParmsKey['RoutingClass'].'"
						             CreateUser="'.$this->setParmsKey['CreateUser'].'"
						             ExternalID="'.$this->setParmsKey['ExternalID'].'" 
						        />
							  </RtFareInfoList>
						</RtFareRequest>';
				break;
			case 9://低价推荐看板
				$xml = '<?xml version="1.0"?>
		                <LowPriceRequest xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xmlns:xsd="http://www.w3.org/2001/XMLSchema">
			            <LowPriceInfoList>
				        <LowPriceInfo 
				        		TripType="'.$this->setParmsKey['TripType'].'" 
				        		DepartCity="'.$this->setParmsKey['DepartCity'].'" 
				        		ArriveCity="'.$this->setParmsKey['ArriveCity'].'" 
				        		Owner="'.$this->setParmsKey['Owner'].'" 
				        		SeatGrade="'.$this->setParmsKey['SeatGrade'].'" 
				        		OutboundTravelDate="'.$this->setParmsKey['OutboundTravelDate'].'" 
				        		InboundTravelDate="'.$this->setParmsKey['InboundTravelDate'].'" 
				        		ProductType="'.$this->setParmsKey['ProductType'].'" 
				        		PassengerNum="'.$this->setParmsKey['PassengerNum'].'" 
				        		PassengerEligibility="'.$this->setParmsKey['PassengerEligibility'].'" 
				        		IsHasTax="'.$this->setParmsKey['IsHasTax'].'" 
				        		LowPriceSort="'.$this->setParmsKey['LowPriceSort'].'" />
			          </LowPriceInfoList>
		            </LowPriceRequest>';
				break;
			case 10://公布运价删除
				$xml = '<?xml version="1.0"?>
					<PublishFareRequest xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xmlns:xsd="http://www.w3.org/2001/XMLSchema">
						 <PublishFareInfoList>
						    <PublishFareInfo FareID="'.$this->setParmsKey['FareId'].'"></PublishFareInfo>
						 </PublishFareInfoList>
						</PublishFareRequest>';

				break;
			case 11://公布运价新增
						/*
							FareID? FareId?
							PolicyID? PolicyId?
							AgentID? AgentId? 
FareID=""
						              PolicyID="'.$v['PolicyID'].'"		
						              CombinablePolicyID="'.$v['CombinablePolicyID'].'"
						              TripType="'.$v['TripType'].'"
						              Owner="'.$v['Owner'].'"
						              DepCity="'.$v['DepCity'].'"
						              ArrCity="'.$v['ArrCity'].'"
						              DepCityExcept="'.$v['DepCityExcept'].'"
						              ArrCityExcept="'.$v['ArrCityExcept'].'"
						              SeatClass="'.$v['SeatClass'].'"
						              IsInterlineApplicable="'.$v['IsInterlineApplicable'].'"
						              InterlineAirlineCode="'.$v['InterlineAirlineCode'].'"
						              InterlineAirlineCodeExcept="'.$v['InterlineAirlineCodeExcept'].'"
						              IsCSApplicable="'.$v['IsCSApplicable'].'"
						              OutboundTravelDate="'.$v['OutboundTravelDate'].'"
						              FCInboundTravelDate="'.$v['FCInboundTravelDate'].'"
						              SalesDate="'.$v['SalesDate'].'"
						              PassengerType="'.$v['PassengerType'].'"
						              Eligibility="'.$v['Eligibility'].'"
						              Commition="'.$v['Commition'].'"
						              Profit="'.$v['Profit'].'"
						              CommissionCalculation="'.$v['CommissionCalculation'].'"
						              TicketingDeadline="'.$v['TicketingDeadline'].'"
						              IsTransferApplication="'.$v['IsTransferApplication'].'"
						              TransferPoint="'.$v['TransferPoint'].'"
						              FareType="'.$v['FareType'].'"
						              InvoiceType="'.$v['InvoiceType'].'"
						              TicketingRemark="'.$v['TicketingRemark'].'"
						              IsAllowOpenJaw="'.$v['IsAllowOpenJaw'].'"
						              WorkingTime="'.$v['WorkingTime'].'"
						              Nationality="'.$v['Nationality'].'"
						              ExcludeNationality="'.$v['ExcludeNationality'].'"
						              IsPrivate="'.$v['IsPrivate'].'"
						              Penalties="'.$v['Penalties'].'"
						              IsForSmallGroup="'.$v['IsForSmallGroup'].'"
						              IsForFilePubFare="'.$v['IsForFilePubFare'].'"
						              AgentId=""							
				    */
					$xml = '<?xml version="1.0"?>
						<PublishFareRequest xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xmlns:xsd="http://www.w3.org/2001/XMLSchema">
							<PublishFareInfoList>';
							// FareID 要获取携程上公布运价的FareId
					foreach ($this->setParmsKey as $k => $v){
					$xml .= '<PublishFareInfo 
					                FareID="'.$k.'"
						              PolicyID="'.$v['PolicyId'].'"		
						              CombinablePolicyID="'.$v['CombinablePolicyID'].'"
						              TripType="'.$v['TripType'].'"
						              Owner="'.$v['Owner'].'"
						              DepCity="'.$v['DepCity'].'"
						              ArrCity="'.$v['ArrCity'].'"
						              DepCityExcept="'.$v['DepCityExcept'].'"
						              ArrCityExcept="'.$v['ArrCityExcept'].'"
						              SeatClass="'.$v['SeatClass'].'"
						              IsInterlineApplicable="'.$v['IsInterlineApplicable'].'"
						              InterlineAirlineCode="'.$v['InterlineAirlineCode'].'"
						              InterlineAirlineCodeExcept="'.$v['InterlineAirlineCodeExcept'].'"
						              IsCSApplicable="'.$v['IsCSApplicable'].'"
						              OutboundTravelDate="'.$v['OutboundTravelDate'].'"
						              FCInboundTravelDate="'.$v['FCInboundTravelDate'].'"
						              SalesDate="'.$v['SalesDate'].'"
						              PassengerType="'.$v['PassengerType'].'"
						              Eligibility="'.$v['Eligibility'].'"
						              Commition="'.$v['Commition'].'"
						              Profit="'.$v['Profit'].'"
						              CommissionCalculation="'.$v['CommissionCalculation'].'"
						              TicketingDeadline="'.$v['TicketingDeadline'].'"
						              IsTransferApplication="'.$v['IsTransferApplication'].'"
						              TransferPoint="'.$v['TransferPoint'].'"
						              FareType="'.$v['FareType'].'"
						              InvoiceType="'.$v['InvoiceType'].'"
						              TicketingRemark="'.$v['TicketingRemark'].'"
						              IsAllowOpenJaw="'.$v['IsAllowOpenJaw'].'"
						              WorkingTime="'.$v['WorkingTime'].'"
						              Nationality="'.$v['Nationality'].'"
						              ExcludeNationality="'.$v['ExcludeNationality'].'"
						              IsPrivate="'.$v['IsPrivate'].'"
						              Penalties="'.$v['Penalties'].'"
						              IsForSmallGroup="'.$v['IsForSmallGroup'].'"
						              IsForFilePubFare="'.$v['IsForFilePubFare'].'"
						              AgentId="" ></PublishFareInfo>';
						}
					$xml .= '</PublishFareInfoList>
						</PublishFareRequest>';
						// print_r($xml);
						// return;
				break;
			case 12://公布运价查询
					$xml = '<?xml version="1.0"?>
                        <PublishFareRequest xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xmlns:xsd="http://www.w3.org/2001/XMLSchema">
                          <PublishFareInfoList>
						    <PublishFareInfo 
								    	FareID="" 
								    	TripType="'.$this->setParmsKey['TripType'].'" 
								    	IsActivated="1" 
								    	Owner="'.$this->setParmsKey['Owner'].'" 
								    	SeatClass="'.$this->setParmsKey['SeatClass'].'" 
								    	DepCity="'.$this->setParmsKey['DepCity'].'" 
								    	ArrCity="'.$this->setParmsKey['ArrCity'].'" 
								    	IsPrivate="'.$this->setParmsKey['IsPrivate'].'" 
								    	PolicyID="" 
								    	CreateUser="'.$this->setParmsKey['CreateUser'].'" 
								    	ExternalID="" />
						  </PublishFareInfoList>
						</PublishFareRequest>';
			    break;
			case 13://公布运价价格调节
			    $xml = '<?xml version="1.0"?>
						<PublishFareRequest xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xmlns:xsd="http://www.w3.org/2001/XMLSchema">
						  <PublishFareInfoList>
						    <PublishFareInfo FareID="'.$this->setParmsKey['FareID'].'" Commition="'.$this->setParmsKey['Commition'].'" Profit="'.$this->setParmsKey['Profit'].'" ></PublishFareInfo>
						  </PublishFareInfoList>
						</PublishFareRequest>';
			    break;
			case 60://订单列表
				$xml = '<?xml version="1.0"?>
						<Request UserName="招商02" UserPassword="0da7b9e7ef4c130dc059613695a84a1c">
						<OpenIssueOrderListRequest>
						<OrderBeginDateTime>'.$this->setParmsKey['Begin'].'</OrderBeginDateTime>
						<OrderEndDateTime>'.$this->setParmsKey['End'].'</OrderEndDateTime>
						</OpenIssueOrderListRequest>
						</Request>';
				break;
			case 63://票号回填
				$xml = '<?xml version="1.0"?>
						<Request UserName="招商02" UserPassword="0da7b9e7ef4c130dc059613695a84a1c">
						  <OpenModifyOrderRequest>
						    <BookingChannel/>
						    <ExtOrderID></ExtOrderID>
						    <IssueBillID>'.$this->setParmsKey['IssueBillID'].'</IssueBillID>
						    <DataChangeType>1</DataChangeType>
						    <PNRLists>
						      '.$this->setParmsKey['PNRList'].'
						    </PNRLists>
						  </OpenModifyOrderRequest>
						</Request>';
				break;
			case 64://PNR授权
				$xml = '<?xml version="1.0"?>
						<Request UserName="招商02" UserPassword="0da7b9e7ef4c130dc059613695a84a1c">
						  <GetPnrPermissionRequest>
						    <OrderID>'.$this->setParmsKey['OrderID'].'</OrderID>
						    <IssueBillID>'.$this->setParmsKey['IssueBillID'].'</IssueBillID>
						    <OfficeNo>'.$this->setParmsKey['OfficeNo'].'</OfficeNo>
						    <PNR>'.$this->setParmsKey['PNR'].'</PNR>
						    <FlightClass>I</FlightClass>
						  </GetPnrPermissionRequest>
						</Request>';
				break;
			case 65://订单详情
				$xml = '<?xml version="1.0"?>
						<Request UserName="招商02" UserPassword="0da7b9e7ef4c130dc059613695a84a1c">
						<OpenIssueBillInfoRequest>
						<IssueBillID>'.$this->setParmsKey['OrderID'].'</IssueBillID>
						</OpenIssueBillInfoRequest>
						</Request>';
				break;
		}
		$this->requestXML=$xml;
		$this->gzipRequestBytes=gzencode($xml,9);
		$this->apiType = $apiType;
	}
	
}