<?php
/**
 * @author Gulam Kabirulla
 * @copyright 2016
 */
?>
<?php
    class Language{
        public $Site;

        public $footerText;
        public $reviewLoadedSuccessfully;
        public $NoReviews;
        public $Reviews;
        public $Blog;
        public $buyNow;
        public $JustPrice;  
        public $Novelty;
        public $Top;
        public $AllContacts;
        public $DeliveryAndGuarantee;
        public $Contacts;
        public $ShippingTo;
        public $SearchUsers;
        public $Login;
        public $SignUp;
        public $Home;
        public $Language;
        public $LetsSearch;
        public $ShoppingCart;
        public $Recommended;
        public $View;
        public $Profile;
        public $Logout;
        public $News;
        public $Messages;
        public $Products;
        public $Sales;
        public $Purchases;
        public $WelcomeTo;
        public $Wishes;
        public $Cooperation;
        public $Subscribe;
        public $Rights;

        public $Email;
        public $Password;
        public $ForgotPass;

        public $ShowMore;
        public $CoversationSearch;
        public $Search;
        public $ConversationTitle;
        public $Ready;
        public $MembersOfTheGroup;
        public $BackToConversation;
        public $AddMember;
        public $Remove;
        public $WriteMessage;
        public $Send;

        public $ErrorVerifyPost;
        public $ChangeInfo;

        public $Goods;
        public $Subscribers;
        public $Subscriptions;
        public $Photoes;
        public $Liked;
        public $AllCategories;
        public $QuestionsAndWishes;
        public $JoinUs;
        public $NickNameUnique;
        public $FullName;
        public $NumberOfPhone;
        
        
        public $SortBy;        
        public $Popular;  
        public $Price;  
        public $PriceDecrease;
        public $PriceIncrease;
        public $PriceFrom;
        public $PriceTo;
        public $Confirm;
        
        public $Noratings;
        public $OverallRating;
        public $from;
        public $Days;
        
        public $DeliveryPrice;
        public $TimeOfDelivery;
        public $MinQuantityForOrder;
        public $Available;
        public $NotAvailable;
        public $Description;
        public $AddToCart;
        
        public $ClearFilters;
        public $ShowFiltres;        
        public $Page;
        public $Quantity;
        public $Delivery;
        public $YourEmailShouldBeDisabled;
        public $YourName;
        public $YourReview;
        public $YourRating;
        public $SameGoods;
        public $AboutCompany;




        function __construct($lan) {
            $this->Site="Ankabootore";
            if($lan=="ru")
            {
                $this->setLanguageRU();
            }
            else if($lan=="ua")
            {
                $this->setLanguageUA();
            }
            else if($lan=="en")
            {
                $this->setLanguageEN();
            }
            else
            {
                http_response_code(404);
                include('404.html'); // provide your own HTML for the error page
                die();
            }
        }
        function getSite()
        {
            return $this->Site;
        }
        function getAllLanguages()
        {
            $arrayLanguages = array('en' => "English", "ru" => "Русский");
            return $arrayLanguages;
        }
        function setLanguageRU()
        {
            $this->reviewLoadedSuccessfully="Отзыв успешно отправлен и будет опубликован после проверки модератором";
            $this->SameGoods="Похожие товары";
            $this->YourRating="Ваша оценка";
            $this->YourReview="Ваш отзыв";
            $this->YourName="Ваше имя";
            $this->YourEmailShouldBeDisabled="Ваш email не будет виден другим пользователям";
            $this->NoReviews="Нет отзывов";
            $this->Delivery="Доставка";
            $this->Quantity="Кол-во";
            $this->Page="Страница";
            $this->ShowFiltres="Показать фильтры";
            $this->ClearFilters="Очистить фильтры";
            $this->Help="Помощь";
            $this->Reviews="Отзывы";
            
            $this->Blog="Блог";
            $this->buyNow="Купить сейчас";
            $this->JustPrice="Цена";
            $this->Novelty="Новинки";
            $this->Top="Топ";
            $this->Recommended="Рекомендуем";
            $this->AllContacts="Все контакты";
            $this->Contacts="Контакты";
            $this->DeliveryAndGuarantee="О доставке";
            $this->ShippingTo="Доставка в";
            $this->SearchUsers="Поиск пользователей";
            $this->Login="Вход";
            $this->SignUp="Регистрация";
            $this->Home="Главная";
            $this->Language="Язык";
            $this->ShoppingCart="Корзина";
            $this->LetsSearch="Поищем вместе...";
            $this->Reсommended="Рекомендуем";
            $this->View="Просмотреть";
            $this->Profile="Профиль";
            $this->News="Новости";
            $this->Messages="Мои сообщения";
            $this->Products="Мои товары";
            $this->Sales="Мои продажи";
            $this->Purchases="Мои покупки";
            $this->WelcomeTo="Приветсвуем вас на";
            $this->Wishes="Желаем вам удачных покупок";// и успешных продаж
            $this->Cooperation="Сотрудничество и реклама";
            $this->Subscribe="Подписывайтесь";
            $this->Rights="Все права защищены";
            $this->Logout="Выйти";
            $this->Email="Email";
            $this->Password="Пароль";
            $this->ForgotPass="Забыли пароль";

            $this->ShowMore="Показать еще";
            $this->CoversationSearch="Поиск беседы";
            $this->Search="Поиск";
            $this->ConversationTitle="Название беседы";
            $this->Ready="Готово";
            $this->MembersOfTheGroup="Участники группы";
            $this->BackToConversation="Вернуться к беседе";
            $this->AddMember="Добавить участника";
            $this->Remove="Удалить";
            $this->WriteMessage="Написать сообщение";
            $this->Send="Отправить";
            $this->ErrorVerifyPost="";
            $this->ChangeInfo="Редактировать информацию";
            $this->Goods="Товары";
            $this->Subscribers="Подписчики";
            $this->Subscriptions="Подписки";
            $this->Photoes="Фотографии";
            $this->Liked="Понравилось";
            $this->AllCategories="Все категории";
            $this->QuestionsAndWishes="Вопросы и пожелания";
            $this->JoinUs="Присоединяйтесь к нам";
            $this->NickNameUnique="Никнейм(уникальный)";
            $this->FullName="Фамилия Имя Отчество";
            $this->NumberOfPhone="Номер телефона";
            $this->SortBy="Сортировка по";
            $this->Popular="Популярное";
            $this->PriceDecrease="Дорогие";
            $this->PriceIncrease="Недорого";
            $this->PriceFrom="Цена от...";
            $this->PriceTo="Цена до...";
            $this->Confirm="Применить";
            $this->Noratings="Нет оценок";
            $this->OverallRating="Общая оценка";
            $this->DeliveryPrice="Цена доставки";
            $this->TimeOfDelivery="Время доставки";
            $this->MinQuantityForOrder="Мин количество для заказа";//Мин количество для заказа(оптом)
            $this->Price="Цена розница";
            $this->Available="в наличии";
            $this->NotAvailable="нет в наличии";
            $this->Description="Описание";
            $this->AddToCart="Купить";
            $this->from="из";
            $this->Days="дней";
            $this->footerText="Сайт создан на платформе <a href='https://ankabootore.com?lan=ru'>ankabootore.com</a>";
            $this->AboutCompany="О компании";
            $this->Press="Пресс-центр";
        }
        function setLanguageUA()
        {
            $this->reviewLoadedSuccessfully="Відгук успішно відправлений і буде опублікований після перевірки модератором";
            $this->SameGoods="Схожі товари";
            $this->YourRating="Ваша оцінка";
            $this->YourReview="Ваш відгук";
            $this->YourName="Ваше ім'я";
            $this->YourEmailShouldBeDisabled="Ваш email не буде видно іншим користувачам";
            $this->LeaveYourReview="Залиште ваш відгук";
            $this->NoReviews="Нема відгуків";
            $this->Delivery="Доставка";
            $this->Quantity="Кількість";
            $this->Page="Сторінка";                      
            $this->Confirm="Застосувати";
            $this->ShowFiltres="Показати фільтри";
            $this->ClearFilters="Очистити фільтри";
            $this->Help="Поміч";
            $this->Reviews="Відгуки";
            $this->Blog="Блог";
            $this->buyNow="Придбати зараз";
            $this->JustPrice="Ціна";
            $this->Novelty="Новинки";
            $this->Top="Топ";
            $this->Recommended="Рекомендуємо";
            $this->AllContacts="Всі контакти";
            $this->Contacts="Контакти";
            $this->DeliveryAndGuarantee="Доставка і гарантії";
            $this->ShippingTo="Доставка в";
            $this->SearchUsers="Пошук користувачів";
            $this->Login="Вхід";
            $this->SignUp="Реєстрація";
            $this->Home="Головна";
            $this->Language="Мова";
            $this->ShoppingCart="Кошик";
            $this->LetsSearch="Пошукаємо разом ...";
            $this->Reсommended="Рекомендуємо";
            $this->View="Переглянути";
            $this->Profile="Профіль";
            $this->News="Новини";
            $this->Messages="Мої повідомлення";
            $this->Products="Мої товари";
            $this->Sales="Мої продажі";
            $this->Purchases="Мої покупки";
            $this->WelcomeTo="Вітаємо вас на";
            $this->Wishes="Бажаємо вам вдалих покупок";// и успешных продаж
            $this->Cooperation="Співпраця і реклама";
            $this->Subscribe="Підписуйтесь";
            $this->Rights="Всі права захищені";
            $this->Logout="Вийти";
            $this->Email="Email";
            $this->Password="Пароль";
            $this->ForgotPass="Забули пароль";

            $this->ShowMore="Показати ще";
            $this->CoversationSearch="Пошук бесіди";
            $this->Search="Пошук";
            $this->ConversationTitle="Назва бесіди";
            $this->Ready="Готово";
            $this->MembersOfTheGroup="Учасники групи";
            $this->BackToConversation="Повернутися до бесіди";
            $this->AddMember="Додати учасника";
            $this->Remove="Вилучити";
            $this->WriteMessage="Написати повідомлення";
            $this->Send="Відправити";
            $this->ErrorVerifyPost="";
            $this->ChangeInfo="Редагувати інформацію";
            $this->Goods="Товари";
            $this->Subscribers="Передплатники";
            $this->Subscriptions="Підписки";
            $this->Photoes="Фотографії";
            $this->Liked="Сподобалося";
            $this->AllCategories="Усі категорії";
            $this->QuestionsAndWishes="Питання та побажання";
            $this->JoinUs="Приєднуйтесь до нас";
            $this->NickNameUnique="Нікнейм(унікальний)";
            $this->FullName="Прізвище ім'я по батькові";
            $this->NumberOfPhone="Номер телефону";
            $this->SortBy="Сортування по";
            $this->Popular="Популярне";
            $this->PriceDecrease="Дорогі";
            $this->PriceIncrease="Недорого";
            $this->PriceFrom="Ціна від...";
            $this->PriceTo="Ціна до ...";
            $this->Confirm="Застосувати";
            $this->Noratings="Немає оцінок";
            $this->OverallRating="Загальна оцінка";
            $this->DeliveryPrice="Вартість доставки";
            $this->TimeOfDelivery="Час доставки";
            $this->MinQuantityForOrder="Мін кількість для замовлення";//Мин количество для заказа(оптом)
            $this->Price="Ціна роздріб";
            $this->Available="в наявності";
            $this->NotAvailable="немає в наявності";
            $this->Description="Опис";
            $this->AddToCart="Купити";
            $this->from="з";
            $this->Days="днів";
            $this->footerText="Сайт створений на платформі <a href='https://ankabootore.com?lan=ua'>ankabootore.com</a>";
            $this->AboutCompany="Про компанію";
            $this->Press="Прес-центр";
        }
        function setLanguageEN()
        {
            $this->reviewLoadedSuccessfully="The review has been sent successfully and will be published after verification by the moderator";
            $this->SameGoods="Related Products";
            $this->YourRating="Your rating";
            $this->YourReview="Your review";              
            $this->YourName="Your name";
            $this->YourEmailShouldBeDisabled="Your email will not be visible to other users";
            $this->LeaveYourReview="Leave your review";
            $this->NoReviews="No reviews";
            $this->Delivery="Delivery";
            $this->Quantity="Quantity";
            $this->Page="Page";       
            $this->Confirm="Confirm";
            $this->ShowFiltres="Show Filtres";
            $this->ClearFilters="Clear filters";
            $this->Help="Help";
            $this->Reviews="Reviews";
            $this->Blog="Blog";
            $this->buyNow="Buy now";
            $this->JustPrice="Price";
            $this->Novelty="Novelty";
            $this->Top="Top";
            $this->Recommended="Recommended";
            $this->AllContacts="All contacts";
            $this->Contacts="Contacts";
            $this->DeliveryAndGuarantee="Delivery and guarantees";
            $this->ShippingTo="Shipping To";
            $this->SearchUsers="Search Users";
            $this->Login="Login";
            $this->SignUp="Sign Up";
            $this->Home="Home";
            $this->Language="Language";
            $this->ShoppingCart="Cart";
            $this->LetsSearch="Lets search together...";
            $this->Reсommended="Reсommended";
            $this->View="View";
            $this->Profile="Profile";
            $this->News="My news";
            $this->Messages="My messages";
            $this->Products="My Products";
            $this->Sales="My Sales";
            $this->Purchases="My Purchases";
            $this->WelcomeTo="Welcome to";
            $this->Wishes="We wish you successful purchases";// and successful sales
            $this->Cooperation="Cooperation and advertising";
            $this->Subscribe="Subscribe";
            $this->Rights="All rights reserved";
            $this->Logout="Logout";
            $this->Email="Email";
            $this->Password="Password";
            $this->ForgotPass="Forgot Password";

            $this->ShowMore="Show more";
            $this->CoversationSearch="Conversation search";
            $this->Search="Search";
            $this->ConversationTitle="Conversation Title";
            $this->Ready="Ready";
            $this->MembersOfTheGroup="Members of the group";
            $this->BackToConversation="Back to conversation";
            $this->AddMember="Add member";
            $this->Remove="Remove";
            $this->WriteMessage="Write message";
            $this->Send="Send";
            $this->ErrorVerifyPost="";
            $this->ChangeInfo="Edit information";
            $this->Goods="Products";
            $this->Subscribers="Subscribers";
            $this->Subscriptions="Subscriptions";
            $this->Photoes="Photoes";
            $this->Liked="Liked";
            $this->AllCategories="All categories";
            $this->QuestionsAndWishes="Questions and wishes";
            $this->JoinUs="Join us";
            $this->NickNameUnique="Nickname(unique)";
            $this->FullName="Full name";
            $this->NumberOfPhone="Phone Number";
            $this->SortBy="Sort by";
            $this->Popular="Popular";
            $this->PriceDecrease="Price (decrease)";
            $this->PriceIncrease="Price (increase)";
            $this->PriceFrom="Price from...";
            $this->PriceTo="Price to...";
            $this->Confirm="Confirm";
            $this->Noratings="No ratings";
            $this->OverallRating="Overall rating";
            $this->DeliveryPrice="Delivery price";
            $this->TimeOfDelivery="Time of delivery";
            $this->MinQuantityForOrder="Min quantity for order";
            $this->Price="Price";
            $this->Available="In stock";
            $this->NotAvailable="Not available";
            $this->Description="Description";
            $this->AddToCart="Buy";
            $this->from="from";
            $this->Days="days";
            $this->footerText="Покупайте качественные товары по выгодной цене на нашем сайте ankabootore.com";
            $this->AboutCompany="About Company";
            $this->Press="Press-center";
        }
        function getPress()
        {
            return $this->Press;
        }
        function getAboutCompany()
        {
            return $this->AboutCompany;
        }
        function getReviewLoadedSuccessfully()
        {
            return $this->reviewLoadedSuccessfully;
        }
        function getSameGoods()
        {
            return $this->SameGoods;
        }
        function getYourRating()
        {
            return $this->YourRating;
        }
        function getYourReview()
        {
            return $this->YourReview;
        }
        function getYourName()
        {
            return $this->YourName;
        }
        function getYourEmailShouldBeDisabled()
        {
            return $this->YourEmailShouldBeDisabled;
        }
        function getLeaveYourReview()
        {
            return $this->LeaveYourReview;
        }
        function getNoReviews()
        {
            return $this->NoReviews;
        }
        function getDelivery()
        {
            return $this->Delivery;
        }
        function getQuantity()
        {
            return $this->Quantity;
        }
        function getPage()
        {
            return $this->Page;
        }
        function getShowFiltres()
        {
            return $this->ShowFiltres;
        }
        function getClearFilters()
        {
            return $this->ClearFilters;
        }
        function getHelp()
        {
            return $this->Help;
        }
        function getReviews()
        {
            return $this->Reviews;
        }
        function getBlog()
        {
            return $this->Blog;
        }
        function getBuyNow()
        {
            return $this->buyNow;
        }
        function getJustPrice()
        {
            return $this->JustPrice;
        }
        function getNovelty()
        {
            return $this->Novelty;
        }
        function getTop()
        {
            return $this->Top;
        }
        function getRecommended()
        {
            return $this->Recommended;
        }
        function getAllContacts()
        {
            return $this->AllContacts;
        }
        function getContacts()
        {
            return $this->Contacts;
        }
        function getDeliveryAndGuarantees()
        {
            return $this->DeliveryAndGuarantee;
        }
        function getFooterText() {
            return $this->footerText;
        }
        function getDays() {
            return $this->Days;
        }
        function getFrom() {
            return $this->from;
        }
        function getAddToCart() {
            return $this->AddToCart;
        }
        function getDescription() {
            return $this->Description;
        }
        function getAvailable() {
            return $this->Available;
        }
        function getNotAvailable() {
            return $this->NotAvailable;
        }
        function getPrice() {
            return $this->Price;
        }
        function getMinQuantityForOrder() {
            return $this->MinQuantityForOrder;
        }
        function getTimeOfDelivery() {
            return $this->TimeOfDelivery;
        }
        function getDeliveryPrice() {
            return $this->DeliveryPrice;
        }
        function getOverallRating() {
            return $this->OverallRating;
        }
        function getNoRatings() {
            return $this->Noratings;
        }
        function getConfirm()
        {
            return $this->Confirm;
        }
        function getPriceTo()
        {
            return $this->PriceTo;
        }
        function getPriceFrom()
        {
            return $this->PriceFrom;
        }
        function getPriceIncrease()
        {
            return $this->PriceIncrease;
        }
        function getPriceDecrease()
        {
            return $this->PriceDecrease;
        }
        function getPopular()
        {
            return $this->Popular;
        }
        function getSortBy()
        {
            return $this->SortBy;
        }
        function getNumberOfPhone()
        {
            return $this->NumberOfPhone;
        }
        function getFullName()
        {
            return $this->FullName;
        }
        function getNickNameUnique()
        {
            return $this->NickNameUnique;
        }
        function getJoinUs()
        {
            return $this->JoinUs;
        }
        function QuestionsAndWishes()
        {
            return $this->QuestionsAndWishes;
        }
        function getAllCategories()
        {
            return $this->AllCategories;
        }
        function getShippingTo()
        {
            return $this->ShippingTo;
        }
        function getSearchUsers()
        {
            return $this->SearchUsers;
        }
        function getLogin()
        {
            return $this->Login;
        }
        function getSignUp()
        {
            return $this->SignUp;
        }
        function getHome()
        {
            return $this->Home;
        }
        function getLanguage()
        {
            return $this->Language;
        }
        function getShoppingCart()
        {
            return $this->ShoppingCart;
        }
        function getSearchTogether()
        {
            return $this->LetsSearch;
        }
        function getReсommended()
        {
            return $this->Reсommended;
        }
        function getView()
        {
            return $this->View;
        }
        function getProfile()
        {
            return $this->Profile;
        }
        function getNews()
        {
            return $this->News;
        }
        function getMessages()
        {
            return $this->Messages;
        }
        function getProducts()
        {
            return $this->Products;
        }
        function getSales()
        {
            return $this->Sales;
        }
        function getPurchases()
        {
            return $this->Purchases;
        }
        function getWelcomeTo()
        {
            return $this->WelcomeTo;
        }
        function getWishes()
        {
            return $this->Wishes;
        }
        function getCooperation()
        {
            return $this->Cooperation.":";
        }
        function getSubscribe()
        {
            return $this->Subscribe;
        }
        function getRights()
        {
            return $this->Rights;
        }
        function getLogout()
        {
            return $this->Logout;
        }

        function getEmail()
        {
            return $this->Email;
        }
        function getPassword()
        {
            return $this->Password;
        }
        function getForgotPass()
        {
            return $this->ForgotPass."?";
        }
        function getShowMore()
        {
            return $this->ShowMore;
        }
        function getCoversationSearch()
        {
            return $this->CoversationSearch;
        }
        function getSearch()
        {
            return $this->Search;
        }
        function getConversationTitle()
        {
            return $this->ConversationTitle;
        }
        function getReady()
        {
            return $this->Ready;
        }
        function getMembersOfTheGroup()
        {
            return $this->MembersOfTheGroup;
        }
        function getBackToConversation()
        {
            return $this->BackToConversation;
        }
        function getAddMember()
        {
            return $this->AddMember;
        }
        function getRemove()
        {
            return $this->Remove;
        }
        function getWriteMessage()
        {
            return $this->WriteMessage;
        }
        function getSend()
        {
            return $this->Send;
        }
        function getErrorVerifyPost()
        {
            return $this->ErrorVerifyPost;
        }
        function getChangeInfo()
        {
            return $this->ChangeInfo;
        }
        function getGoods()
        {
            return $this->Goods;
        }
        function getSubscribers()
        {
            return $this->Subscribers;
        }
        function getSubscriptions()
        {
            return $this->Subscriptions;
        }
        function getPhotoes()
        {
            return $this->Photoes;
        }
        function getLiked()
        {
            return $this->Liked;
        }
    }
?>