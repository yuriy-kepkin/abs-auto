var app = {
    index: function() {
        console.log('loaded');
    },
    /**
     * Вешается нужный экшн в зависимости от класса кнопки
     */
    bindAction: function(elem) {
        var elemClasses = elem.classList,
            neededClass = /app-action_/;
    
        elemClasses.forEach(function(name) {
            // Определяем, какая кнопка была нажата
            if (name.match(neededClass)) {
                var action = name.split('_')[1];

                // Вызываем нужный экшн
                app[action]();

            }
        });
    },
    renderData: function(data) {
        var tbody = $('.app_reports-results-table')[0];

        // Очищаем таблицу
        $(tbody).html('');

        // Заполняем пришедшими данными
        data.forEach(function(elem) {
            var row = "<tr><td>" + elem.id + "</td><td>" + elem.title + "</td><td>" + elem.price + "</td></tr>";

            $(tbody).append(row);
        });
    },
    /**
     * Рендерим информацию в инфоблок над таблицей (а также кнопку для показа данных)
     * Если данные не переданы, то рендерятся дополнительные параметры
     */
    renderInfoBlock: function(span = null, data = null) {
        var infoBlock = $('.app_reports-filters-info')[0],
            tbody = $('.app_reports-results-table')[0],
            btn = '<button type="button" class="btn btn-outline-info btn-sm app-action_showresults">Показать</button>';

        // Очищаем инфоблок и таблицу
        $(infoBlock).html('');
        $(tbody).html('');
        
        if (data == null) {
            var dateIntervalForm = '<form class="form-inline date-filter">';
                dateIntervalForm += '<label for="date-from" class="mr-sm-2">с:</label>';
                dateIntervalForm += '<input type="text" class="form-control mb-2 mr-sm-2" placeholder="2020-10-19" id="date-from">';
                dateIntervalForm += '<label for="date-to" class="mr-sm-2">по:</label>';
                dateIntervalForm += '<input type="text" class="form-control mb-2 mr-sm-2" placeholder="2020-10-19" id="date-to">';
                dateIntervalForm += '</form>';
            
            var btn = '<button type="button" class="btn btn-outline-info btn-sm app-action_getresults">Запросить</button>';

            $(infoBlock).append(dateIntervalForm);
            $(infoBlock).append(btn);

            $('.app-action_getresults').on('click', app.interval);
        } else {  
            $(infoBlock).append(span);  
            $(infoBlock).append(btn);
            $('.app-action_showresults').on('click', {
                data: data
            }, app.showResults);
        }
    },
    showResults: function(e) {
        // Костыль для interval, где e.data не массив, а объект с data и midPrice 
        if (Array.isArray(e.data.data)) {
            var data = e.data.data;
        } else {
            var data = e.data.data.data;
        }

        app.renderData(data);
    },
    upload: function() {
        var file_data = $('#app_load-file').prop('files')[0],
            form_data = new FormData();

        form_data.append('file', file_data);
        form_data.append('app_req', 'upload');

        $.ajax({
            url: 'actions.php',
            dataType: 'text',
            type: 'post',
            cache: false,
            contentType: false,
            processData: false,
            data: form_data,
            success: function(res) {
                if (res) {
                    var uploaded = JSON.parse(res);

                    console.log(uploaded);

                    app.renderData(uploaded);
                } else {
                    alert("Вы не выбрали файл для загрузки!");
                }
            }
        });
    },
    lasthours: function() {
        // По-умолчанию 3 часа
        var hours = 3;

        $.ajax({
            url: 'actions.php',
            type: 'post',
            data: {
                app_req: 'lasthours',
                hours: hours
            },
            success: function(res) {
                var lastHoursData = JSON.parse(res),
                    span = "<span>За предыдущие " + hours + " час(а) выпарсено наименований: " + lastHoursData.length + "</span>";

                app.renderInfoBlock(span, lastHoursData);
            }
        });
    },
    midprice: function() {
        $.ajax({
            url: 'actions.php',
            type: 'post',
            data: {
                app_req: 'midprice'
            },
            success: function(res) {
                var midPriceData = JSON.parse(res),
                    span = "<span>Средняя цена товара за сутки: " + midPriceData.midPrice + "р.</span>";

                app.renderInfoBlock(span, midPriceData.data);
            }
        });
    },
    interval: function() {
        var dateFrom = $('#date-from').val(),
            dateTo = $('#date-to').val();

        if (dateFrom && dateTo) {
            $.ajax({
                url: 'actions.php',
                type: 'post',
                data: {
                    app_req: 'interval',
                    date_from: dateFrom,
                    date_to: dateTo
                },
                success: function(res) {
                    var intervalData = JSON.parse(res),
                        span = "<span>Товары (3) с минимальной ценой за промежуток с " + dateFrom + " по " + dateTo + "</span>";

                    app.renderInfoBlock(span, intervalData);
                }
            });
        } else {
            app.renderInfoBlock();
        }
    }
};

window.addEventListener("load", app.index);
