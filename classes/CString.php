<?php
/**
 * @author Gulam Kabirulla
 * @copyright 2016
 */
?>
<?php
    trait MyString{
        function CheckForPriceAndVariantsCount($Variants,$Prices)
        {
            if(!$this->CheckForVariantsInput($Variants)||!$this->CheckForVariantsPrices($Prices))
            {
                return false;
            }
            $Prices=explode("}",$Prices);
            $Variants=explode("}",$Variants);
            if(count($Prices)==count($Variants))
            {
                return true;
            }
            return false;
        }
        function CheckForVariantsPrices($Prices)
        {
            $Prices=explode("}",$Prices);
            if(count($Prices)<2)
            {
                return false;
            }
            for($i=0;$i<count($Prices)-1;$i++)
            {
                if(floatval($Prices[$i]<=0.0))
                {
                    return false;
                }
            }
            return true;
        }
        function CheckForVariantsInput($Variants)
        {
            $Variants=explode("}",$Variants);
            if(count($Variants)<2)
            {
                return false;
            }
            for($i=0;$i<count($Variants)-1;$i++)
            {
                $VariantsValue=explode(",",$Variants[$i]);
                for($j=0;$j<count($VariantsValue)-1;$j++)
                {
                    $VariantsProperyAndSetting=explode("_",$VariantsValue[$j]);
                    if((int)$VariantsProperyAndSetting[0]<1||(int)$VariantsProperyAndSetting[1]<1)
                    {
                        return false;
                    }
                }
            }
            return true;
        }
        function CheckDeliverString($deliver)
        {
            return true;
            $deliver=explode("}",$deliver);
            if(count($deliver)<1)
            {
                return false;
            }
            for($i=0;$deliver[$i]!=null;$i++)
            {
                $deliverValue=explode(",",$deliver[$i]);
                if(count($deliverValue)!=4||(int)$deliverValue[0]<0||(int)$deliverValue[1]<1||floatval($deliverValue[2])<0.0||(int)$deliverValue[3]<1)
                {
                    return false;
                }
            }
            return true;
        }
        function CheckDeliverNotString($deliverNot)
        {
            $deliverNot=explode("_",$deliverNot);
            if(count($deliverNot)<2)
            {
                return false;
            }
            for($i=0;$deliverNot[$i]!=null;$i++)
            {
                if((int)$deliverNot[$i]<1)
                {
                    return false;
                }
            }
            return true;
        }
        function CheckForUniqueDeliverCountry($deliver)
        {
            if(!$this->CheckDeliverString($deliver))
            {
                return false;
            }
            $deliver=explode("}",$deliver);
            for($i=0;$deliver[$i+1]!=null;$i++)
            {
                $deliverCountry=explode(",",$deliver[$i])[0];
                for($j=$i+1;$deliver[$j]!=null;$j++)
                {
                    $deliverCountry2=explode(",",$deliver[$j])[0];
                    if((int)$deliverCountry==(int)$deliverCountry2)
                    {
                        return false;
                    }
                }
            }
            return true;
        }
        function CheckForUniqueDeliverNotCountry($deliverNot)
        {
            if(!$this->CheckDeliverNotString($deliverNot))
            {
                return false;
            }
            $massiveE=explode("_",$deliverNot);
            for($i=0;$massiveE[$i+1]!=null;$i++)
            {
                for($j=$i+1;$massiveE[$j]!=null;$j++)
                {
                    if((int)$massiveE[$i]==(int)$massiveE[$j])
                    {
                        return false;
                    }
                }
            }
            return true;
        }
        function CheckForEqualCountry($deliver,$deliverNot)
        {
            if(!$this->CheckDeliverNotString($deliverNot)||!$this->CheckDeliverString($deliver))
            {
                return false;
            }
            $deliver=explode("}",$deliver);
            $deliverNot=explode("_",$deliverNot);            
            for($i=0;$deliver[$i]!=null;$i++)
            {
                $deliverCountry=explode(",",$deliver[$i])[0];
                for($j=0;$deliverNot[$j]!=null;$j++)
                {
                    if((int)$deliverNot[$j]==(int)$deliverCountry)
                    {
                        return true;
                    }
                }
            }
            return false;        
        }
    }
?>
