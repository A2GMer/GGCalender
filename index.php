<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ゴールドジムの休館日を俺に教えて</title>
    <style>
        table {
            width: 100%;
            border-collapse: collapse;
        }
        td, th {
            padding: 10px;
            text-align: center;
            border: 1px solid #ddd;
        }
        td {
            cursor: pointer;
        }
        .holiday {
            background-color: red;
            color: white;
        }
        h3 {
            text-decoration:underline;
        }
    </style>
</head>
<body>
    <h1>ゴールドジムの休館日を俺に教えて</h1>
    <h3 bold>大阪梅田店</h3>
    <div id="calendar">
        <!-- カレンダーはここに表示されます -->
    </div>
</body>
</html>

<?php

date_default_timezone_set("Asia/Tokyo");
require_once('config.php');

try {
    $pdo = new PDO(DSN, DB_USER, DB_PASS);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    echo 'データベース接続失敗: ' . $e->getMessage();
    exit;
}

// 現在の月と年を取得
$year = date("Y");
$month = date("m");

// その月の最初の日と最後の日を取得
$first_day_of_month = date('w', strtotime("$year-$month-01"));
$total_days = date('t', strtotime("$year-$month-01"));

// 休館日を取得
$query = $pdo->query("SELECT date FROM holidays WHERE MONTH(date) = $month AND YEAR(date) = $year");
$holidays = $query->fetchAll(PDO::FETCH_COLUMN);

// 休館日を追加する処理
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['day'])) {
    $day = $_POST['day'];
    $date = "$year-$month-$day";

    // 登録済みかどうか確認
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM holidays WHERE date = ?");
    $stmt->execute([$date]);
    $exists = $stmt->fetchColumn();

    if (!$exists) {
        $stmt = $pdo->prepare("INSERT INTO holidays (date) VALUES (?)");
        $stmt->execute([$date]);
    } else {
        // 既に登録済みの場合は削除する
        $stmt = $pdo->prepare("DELETE FROM holidays WHERE date = ?");
        $stmt->execute([$date]);
    }

    // リロードして反映
    header("Location: " . $_SERVER['PHP_SELF']);
    exit;
}

// カレンダーの出力
echo $month . "月";
echo "<table>";
echo "<tr><th>日</th><th>月</th><th>火</th><th>水</th><th>木</th><th>金</th><th>土</th></tr><tr>";

// 空のセルを追加
for ($i = 0; $i < $first_day_of_month; $i++) {
    echo "<td></td>";
}

// 各日をループして表示
for ($day = 1; $day <= $total_days; $day++) {
    $date = "$year-$month-".str_pad($day, 2, '0', STR_PAD_LEFT); // 日付を2桁に整形
    $class = in_array($date, $holidays) ? 'holiday' : '';
    echo "<td class='$class' onclick='confirmHoliday($day)'>$day</td>";
    if (($day + $first_day_of_month) % 7 == 0) {
        echo "</tr><tr>";
    }
}


// 空のセルを追加して行を完成させる
while (($day + $first_day_of_month) % 7 != 1) {
    echo "<td></td>";
    $day++;
}

echo "</tr></table>";
?>

<script>
function confirmHoliday(day) {
    if (confirm(day + "日を休館日として登録しますか？")) {
        var form = document.createElement('form');
        form.method = 'POST';
        form.action = '';
        
        var input = document.createElement('input');
        input.type = 'hidden';
        input.name = 'day';
        input.value = day;
        
        form.appendChild(input);
        document.body.appendChild(form);
        form.submit();
    }
}
</script>
