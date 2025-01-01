<?
    file_put_contents('email_log.txt', "Script executed\n");

    $input = file_get_contents('php://stdin');
    
    if ($input === false) {
        file_put_contents('email_log.txt', "Failed to read input\n", FILE_APPEND);
    } else {
        file_put_contents('email_log.txt', "Email Content:\n" . $input . "\n", FILE_APPEND);
    }

    file_put_contents('email_log.txt', "Script completed\n", FILE_APPEND);
    #change to pdo

    /*$data = file_get_contents("php://stdin");
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    $retailerString = preg_match('/^From:\s*(.*)$/mi', $data, $a) ? $a[1] : '';
    
    if($retailerString == '"Best Buy Notifications" <BestBuyInfo@emailinfo.bestbuy.com>'){
        $retailer = 0;
        $ref = substr($data, strpos($data, 'BBY01-') + 6, 12);
        
        file_put_contents(__DIR__.'/email_log.txt', $data);
        
        curl_setopt($ch, CURLOPT_HEADER, 1);
        curl_setopt($ch, CURLOPT_NOBODY, 1);
        curl_setopt($ch, CURLOPT_USERAGENT, "/");
        
        $subject = preg_match('/^Subject:\s*(.*)$/mi', $data, $a) ? $a[1] : '';
        if($subject == "📦 Your package is going to be delivered. 📦"){
            preg_match('/<a href="https:\/\/click\.emailinfo2\.bestbuy\.com\/\?qs=([a-zA-Z0-9]+)".*?>\s*Track Package\s*<\/a>/', $data, $a);
        }else{
            preg_match('/<a href="https:\/\/click\.emailinfo2\.bestbuy\.com\/\?qs=([a-zA-Z0-9]+)".*?>\s*View Order Details\s*<\/a>/', $data, $a);
        }
        curl_setopt($ch, CURLOPT_URL, "https://click.emailinfo2.bestbuy.com/?qs=".$a[1]);
        $t = curl_exec($ch);
        
        if($subject == "Thanks for your order." || $subject == "Your Best Buy order has been canceled."){
            $step = ($subject == "Thanks for your order.") ? 1 : 0;
            $s1 = strpos($t,'t1');
            $s2 = strpos($t,'t2');
            curl_setopt($ch, CURLOPT_URL, "https://www.bestbuy.com/profile/ss/orders/email-redirect/order-status?t1=".substr($t,$s1+5,$s2-$s1-18)."&t2=".substr($t,$s2+5,43));
        }elseif($subject == "We have your tracking number."){
            $step = 3;
            curl_setopt($ch, CURLOPT_URL, "https://www.bestbuy.com/profile/ss/orders/email-redirect/order-status?token=".substr($t, strpos($t, 'token') + 8, 44)); #check consistency
        }elseif($subject == "📦 Your package is going to be delivered. 📦"){
            $step = 4;
            $s1 = strpos($t,'t1');
            $s2 = strpos($t,'t2');
            curl_setopt($ch, CURLOPT_URL, "https://www.bestbuy.com/profile/ss/orders/email-redirect/order-status?t1=".substr($t,$s1+5,$s2-$s1-18)."&t2=".substr($t,$s2+5,43));
        }
        
        if(isset($step)){
            $v = curl_exec($ch);
            curl_setopt($ch, CURLOPT_URL, "https://www.bestbuy.com/profile/ss/api/v1/orders/BBY01-".$ref);
            curl_setopt($ch, CURLOPT_COOKIE, "vt=".substr($v,strpos($v,'vt')+3,36)."; SID;");
            curl_setopt($ch, CURLOPT_HEADER, 0);
            curl_setopt($ch, CURLOPT_NOBODY, 0);
            $orderContents = json_decode(curl_exec($ch))->order->items;
            if($step == 3){
                file_put_contents(__DIR__ . '/email_log.txt', print_r(substr($v,strpos($v,'vt')+3,36)."; SID;", 1), FILE_APPEND);
                file_put_contents(__DIR__ . '/email_log.txt', print_r($orderContents, 1), FILE_APPEND);
            }
        }
    }else exit;
    
    $DATABASE_HOST = "198.12.245.3";
    $DATABASE_USER = "eric1298awdiuxohadbuytofill123";
    $DATABASE_PASS = "wZR}v&xg=S0Fsadwa3213damn";
    $DATABASE_NAME = "buytofill";
    
    if($step === 0 || $step === 1){
        $content = [];
        foreach($orderContents as $item){
            if(isset($content[$item->sku])){
                $content[$item->sku] += $item->quantity;
            }else{
                $content[$item->sku] = $item->quantity;
            }
        }  # UID should be in retailerOrders not commits
        
        $p = preg_match('/^X-Forwarded-To:\s*api\+([a-zA-Z]{5})@buytofill\.com$/mi', $data, $a) ? $a[1] : exit;
        $uid = (ord($p[0])-64)*(ord($p[1])-64)*(ord($p[2])-64)*(ord($p[3])-64)*(ord($p[4])-64);
        
        date_default_timezone_set('America/New_York');
        $date = date('mdHi');
        
        $conn = new mysqli($DATABASE_HOST, $DATABASE_USER, $DATABASE_PASS, $DATABASE_NAME);
        if($step === 0){
            $stmt = $conn->prepare("SELECT 1 FROM `retailerOrders` WHERE ref = ?");
            $stmt->bind_param("s", $ref);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if($result->num_rows > 0){
                $stmt = $conn->prepare("UPDATE `retailerOrders` SET status = 0 WHERE ref = ?");
                $stmt->bind_param("s", $ref);
                $exists = true;
            }else{
                $stmt = $conn->prepare("INSERT INTO `retailerOrders` (ref, retailer, date, status) VALUES (?, ?, ?, 0)");
                $stmt->bind_param("sii", $ref, $retailer, $date);
                $exists = false;
            }
        }else{
            $stmt = $conn->prepare("INSERT INTO `retailerOrders` (ref, retailer, date) VALUES (?, ?, ?);");
            $stmt->bind_param("sii", $ref, $retailer, $date);
        }
        
        $stmt->execute();
        $stmt->close();
        
        $rid = $conn->insert_id;
        
        #file_put_contents(__DIR__.'/email_log.txt', $rid, FILE_APPEND);
        
        $stmt = $conn->prepare("INSERT INTO `commit` (uid, oid, rid, qty) VALUES (?, (SELECT o.id FROM `retailerKeys` rk INNER JOIN `order` o ON o.pid = rk.id WHERE rk.retailer = $retailer AND rk.ref = ? AND o.status = 1), ?, ?);");
        foreach ($content as $sku => $quantity) {
            try{
                $stmt->bind_param("isii", $uid, $sku, $rid, $quantity);
                $stmt->execute();
            }catch(Exception $e){
                file_put_contents(__DIR__.'/email_log.txt', "Make sure there is an order for $sku and retailerKeys.retailer = $retailer and retailerKeys.ref = $sku exists (" . $e->getMessage() . ")\n", FILE_APPEND);
            }
        }
        
        $stmt->close();
    
        $conn->close();
    }elseif($step == 3 || $step == 4){
        $content = [];
        foreach ($orderContents as $item) {
            $sku = $item->sku;
            $quantity = $item->quantity;
        
            if (!isset($content[$sku])) {
                $content[$sku][] = [$item->fulfillment->tracking->trackingNumber, $quantity];
            } else {
                $content[$sku][1] += $quantity;
            }
        }

        
        $conn = new mysqli($DATABASE_HOST, $DATABASE_USER, $DATABASE_PASS, $DATABASE_NAME);
        $stmt = $conn->prepare("SELECT rk.id, rk.ref FROM retailerOrders ro JOIN `commit` c ON ro.id = c.rid INNER JOIN `order` o ON o.id = c.oid INNER JOIN retailerKeys rk ON rk.id = o.pid AND rk.retailer = $retailer WHERE ro.ref = ?;");
        $stmt->bind_param("s", $ref);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $sku = $row['ref'];
                if (array_key_exists($sku, $content)) {
                    $info = $content[$sku][0];
                    $iStmt = $conn->prepare("
                        INSERT INTO trackings (cid, qty, tracking) 
                        SELECT c.id, ?, ? FROM retailerKeys rk 
                        INNER JOIN `order` o ON o.pid = rk.id 
                        INNER JOIN retailerOrders ro ON ro.ref = ?
                        INNER JOIN `commit` c ON c.oid = o.id AND c.rid = ro.id AND ro.retailer = $retailer
                        WHERE rk.ref = ? AND NOT EXISTS (SELECT 1 FROM trackings t WHERE t.cid = c.id AND t.tracking = ?);");
                    $iStmt->bind_param("issss", $info[1], $info[0], $ref, $sku, $info[0]);
                    $iStmt->execute();
                    $iStmt->close();
                }
            }
        }
        
        $stmt->close();
        $conn->close();
    }
    
    curl_close($ch);*/
    
    exit;
?>