
#### Для запуска приложения:
- Если используется Apache файл .htaccess лежит в папке public
- Если используется nginx конфиг находится в `docker/nginx/default.conf`
- Если есть возможность установить docker:
  - `docker-compose up -d` установит и запустит контейнеры PHP, Nginx и MySQL
  - `docker exec -it comment_tree_php bash` зайти внутрь контейнера с приложением
  - `bin/console doctrine:migrations migrate` запустит миграцию для таблицы БД
  - `bin/console doctrine:fixtures:load` загрузит по 20 комментариев с 1-го по 6-ой уровней вложенности
- Если нет возможности запустить команду для миграции запрос на создание таблицы можно взять из `migrations\Version20230503164235.php`

#### Основные подводные камни задачи:
1. Построение древа комментариев после выгрузки из БД списка комментариев. Кроме реализанного мной варианта с построением дерева на беке и возвращением уже сверствнного дерева я рассматривал вариант возвращения просто списка комментариеви отрисовка дерева на стороне фронта. В данном случае не было бы рекурсии, тк Jquery итерировалось бы по массиву и аппендила чайлды к родителю (либо к основному контейнеру для комментариев первого уровня), но это нагрузка на клиентское устройство, поэтому я выбрал вариант с обработкой на бэке. 
    
   Если бы можно было чуть поменять логику, я бы выбрал сделал реализацию, как на сайте pikabu.ru, они показывают только комментарии первого уровня, с подгрузкой при прокрутке, и можно развернуть ветку комментариев, легко сделать столбец в БД root_id и по нему подгружать ветку комментариев. 
2. Выгрузка данных из БД, первая мысль была, что у комментария должны быть только поля `id` и `parent_id`, но с таким набором, мы не можем ограничить выборку по уровню. Поэтому я добвил поля `rang` и `thirdLevelRoot`, по полю `rang` удобно выбирать только интересующие нас уровни вложенности. Поле `thirdLevelRoot` узкоспециализировнное для данной задачи, и хранит родителя третьего уровня для комментария (либо null, если `rang` <= 3), что позволяет одним запросом выбрать все нужные комментарии при нажатии кнопки "Показать ещё". К сожалению, если нам понадобится изначально показывать только первые два уровня, а не три, придется писать скрипт, который пересчитывает значение этой колонки. Поэтому если есть перспектива подобных изменений, лучше поискать другое решение. 
3. Удаление комментария в дереве. Если мы удаляем комментарий, то у нашел три возможных поведения: 
   1. Удаление вместе с чайлдами. С точки зрения логики плохо, так как человек может удалить комментарии других пользователей. С точки зрения реализации, кол-во запросов к БД растет прямопропорционально кол-ву уровней вложенности ответов на комментарий, тк у нас нет критерия, чтобы выбрать все разом (если конечно, удаляется не комммент третьего уровня:) но это частный случай)
   2. Удаление комментария с поднятием ранга ответов на +1 у каждого. С точки зрения логики плохо, если удается коммментарий 2-ого уровня например, то у его родителя могут появится дети, которые к нему не относятся. С точки зрения реализации нужно пересохранить всех детей на всех уровнях вложенности, много запросов к БД
   3. Удаление содержимого, но сохранение узла дерева (видел это на большинстве, если не на всех платформах с древовидными комментариями). Позволяет сохранить структуру дерева, но при этом сам коммент исчезает

4. Большие нагрузки: если комментарии активно добавляются/запрашиваются, то нужно добавлять шардированиеи и тд

Из крупных проблем, которые нужно решить перед началом реализации это все, что я нашел.

Некотрые вещи в этой задаче ещё нужно доделать (например больше интерактивности на фронте, валидация, тесты на получение дерева комментариев на беке), но по описанию задачи я понял, что могу просто указать их необходимость, не третя время на реализацию
