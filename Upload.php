<?php

class Upload {
    private $pdo = null;
    private $stmt = null;

    /**
     * При создании экземляра устанавливаем соединение с БД
     */
    public function __construct() {
        try {
            $this->pdo = new PDO(
              "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET, DB_USER, DB_PASSWORD, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false
              ]
            );

            return true;
        } catch (Exception $ex) {
            $this->CB->verbose(0, "DB", $ex->getMessage(), "", 1);
        }
    }

    /**
     * Закрываем соединение, когда все операции выполнены
     */
    function __destruct () {
        if ($this->stmt !== null) {
            $this->stmt = null;
        }

        if ($this->pdo !== null) {
            $this->pdo = null;
        }
    }

    /**
     * Добавить данные из .csv в БД и вернуть их
     */
    public function addFileData($file_name) {
        $file_to_parse = 'uploads/' . $file_name;

        $parsed_data = $this->parseFileData($file_to_parse);

        $added_data = [];

        foreach ($parsed_data as $item) {
            $sql = "INSERT INTO test (title, price, date) VALUES (:title, :price, :date)";

            $this->stmt = $this->pdo->prepare($sql);
            $this->stmt->execute([
                'title' => $item[0],
                'price' => $item[1],
                'date' => date('Y-m-d H:i:s', time())
            ]);

            $this->stmt = $this->pdo->prepare("SELECT * FROM test WHERE id = ?");
            $this->stmt->execute([
                $this->pdo->lastInsertId()
            ]);

            $added_data[] = $this->stmt->fetchObject();
        }

        return $added_data;
    }

    /**
     * Распарсить данные из .csv
     */
    public function parseFileData($file_to_parse) {
        $delimiter = ';';

        $csv = file_get_contents($file_to_parse);
        $rows = explode(PHP_EOL, $csv);
        $data = [];

        foreach ($rows as $row) {
            $data[] = explode($delimiter, $row);
        }

        // Данные из каких столбцов нужно получить
        $need_title = 'Название';
        $need_price = 'Цена';

        // Индексы данных (под нужными столбцами) в массивах
        $key_title = null;
        $key_price = null;

        $result = [];

        foreach ($data as $elemKey => $elemValue) {
            foreach ($elemValue as $key => $value) {
                if ($elemKey == 0) {
                    // Записываем индекс названия конкретной колонки, чтобы потом (ниже)
                    // знать, к какому индексу обращаться и записывать значение
                    if ($value == $need_title) {
                        $key_title = $key;
                    } else if ($value == $need_price) {
                        $key_price = $key;
                    }
                } else {
                    if ($key == $key_title) {
                        $result[$elemKey][] = $value;
                    } else if ($key == $key_price) {
                        $result[$elemKey][] = $value;
                    }
                }
            }
        }

        return $result;
    }

    /**
     * Сколько наименований товаров было выпарсено за предыдущие $hours часов, относительно текущего времени
     * По-умолчанию 3 часа
     */
    public function lastHours($hours = 3) {
        $sql = "SELECT * FROM test WHERE test.date >= NOW() - INTERVAL $hours HOUR";

        $this->stmt = $this->pdo->prepare($sql);
        $this->stmt->execute();

        $result = $this->stmt->fetchAll();

        return $result;
    }

    /**
     * Средняя цена выпарсенного товара за сутки, относительно текущего времени
     */
    public function midPrice() {
        $sql = "SELECT * FROM test WHERE test.date >= NOW() - INTERVAL 1 DAY";

        $this->stmt = $this->pdo->prepare($sql);
        $this->stmt->execute();

        $data = $this->stmt->fetchAll();
        $prices = [];

        foreach ($data as $item) {
            $prices[] = $item['price'];
        }

        $result = [
            'data' => $data,
            'midPrice' => array_sum($prices) / count($prices)
        ];

        return $result;
    }

    /**
     * 3 товара с минимальной ценой, за промежуток времени "с - по"
     */
    public function interval($date_from, $date_to) {
        $sql = "SELECT * FROM test WHERE test.date BETWEEN '$date_from 00:00:00' AND '$date_to 23:59:59' ORDER BY test.price LIMIT 3";

        $this->stmt = $this->pdo->prepare($sql);
        $this->stmt->execute();

        $result = $this->stmt->fetchAll();

        return $result;        
    }
}