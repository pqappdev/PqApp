PqApp
=======
Bu sınıf bir yii extension'ıdır framework altında extensions klasörü altına eklenmelidir<br/>
<code>
	<pre>
		Yii::import('ext.PqApp.App');
	</pre>
</code>
Örnek olarak çağrılıp basitçe kullanımı
<code>
	<pre>
		$userLogin=App::load()->facebook->user_login; //signed_request datasında user id varmı diye kontrol edip boolean sonuç verir
		$pageLiked = App::load()->facebook->page_liked; //sayfa altında çalışıyor ise sayfanın like edilip edilmediğini verir.
	</pre>
</code>
Basit şekilde Kullanıcı kaydı işlemi
<code>
	<pre>
		//fql'de en fazla 250 sonuç veridiğinden bazı kayıt dosyaları async çalışmaktadır bu yüzden async parametresi belirlenmelidir örnek
		App::load()->user->facebook->asynCall = 'User/FbSave/'; //redirect_uri.asynCall olarak birleştirecektir adresi.!
		App::load()->user->facebook->save(); //kullanıcı fql datasını tablolarını oluşturarak yazar

	</pre>
</code>


Uygulamanın ayar dosyaları protected/config/app altına eklenmelidir default dosya ismi default.php dir içeriği:
<code>
	<pre>
		return array(
		    'facebook' => array(
		        'appId'                 => '', //facebook uygulama ıd si
		        'appSecret'             => '', //facebook uygulama secret bilgisi
		        'appLink'               => '', //uygulama linki hem apps hemde tab de çalışacak ise apps linki verilmelidir sadece sekmede ise sekme linki
		        'pageLikeLink'          => '', //uygulama'da like zorunluluğu olan sayfanın linki
		        'pageId'                => '', //uygulamanın çalıştığı sayfanın id si
		        'appPageLink'           => '', //uygulama sekme linki
		        'redirectUri'           => '', //uygulamanın çalıştığı adres 
		        'scope'                 => 'user_likes,user_about_me,user_interests,user_education_history,user_work_history,email,user_birthday,user_hometown,user_location,user_relationships,user_relationship_details,user_website,publish_actions',
		        'forceUnderFacebook'    => false, //true ise sınıf yüklemesinde kontrol edip istenen adres altına çalışmaya zorlanır fakat ajax call ile yapılan işlemlerde sorun yaratmaması için false işaretli
		        'checkMissingPermission'=> false, //sınıf yüklendiğinde eksik izin varmı diye kontrol eder yavaşlığa sebep verebilir
		        'fileUpload'            => false, //sınıf facebook php sdk dan türetildiği için facebook sdk config deki fileUpload ayarı
		        'tables'                => array('user','user_likes'), //uygulamada kaydedilmesi istenen tablolar bunlar facebookusersave.php içinde sabitlenmişti
		        'table_prefix'          => 'fb_', //sınıfın oluşturduğu tabloların başlangıç prefix'i
		    ), 
		    'settings' => array(
		        'endDate' => '2014-01-01 00:00:00', //genel uygulama bilgisi uygulamanın kapanış tarihi
		    ),
		);		
	</pre>
</code>
Extension'ın hangi config dosyasını kullanıcağını aşağıdaki "DEFAULT_APP_CONFIG" ile belirleyebilirsiniz
<code>
	<pre>
		define('DEFAULT_APP_CONFIG', 'default');
	</pre>
</code>
Environmental ortamlarında farklı config dosyaları kullanmak için yii config dosyaları içine hangi appconfig'i yazılması gerektiğini belirterek controller init function'da ayarlayabilirsiniz.