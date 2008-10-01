<?
/*-------------------------------------------------------
*
*   LiveStreet Engine Social Networking
*   Copyright © 2008 Mzhelskiy Maxim
*
*--------------------------------------------------------
*
*   Official site: www.livestreet.ru
*   Contact e-mail: rus.engine@gmail.com
*
*   GNU General Public License, version 2:
*   http://www.gnu.org/licenses/old-licenses/gpl-2.0.html
*
---------------------------------------------------------
*/

/**
 * Обработка УРЛа вида /link/ - управление своими топиками(тип: ссылка)
 *
 */
class ActionLink extends Action {
	/**
	 * Меню
	 *
	 * @var unknown_type
	 */
	protected $sMenuItemSelect='link';
	/**
	 * СубМеню
	 *
	 * @var unknown_type
	 */
	protected $sMenuSubItemSelect='add';
	/**
	 * Текущий юзер
	 *
	 * @var unknown_type
	 */
	protected $oUserCurrent=null;
	
	/**
	 * Инициализация
	 *
	 * @return unknown
	 */
	public function Init() {			
		$this->oUserCurrent=$this->User_GetUserCurrent();
		$this->SetDefaultEvent('add');		
		$this->Viewer_AddHtmlTitle('Ссылки');
	}
	/**
	 * Регистрируем евенты
	 *
	 */
	protected function RegisterEvent() {		
		$this->AddEvent('add','EventAdd');					
		$this->AddEvent('edit','EventEdit');
		$this->AddEvent('go','EventGo');	
	}
		
	
	/**********************************************************************************
	 ************************ РЕАЛИЗАЦИЯ ЭКШЕНА ***************************************
	 **********************************************************************************
	 */
	
	/**
	 * Переход по ссылке
	 *
	 * @return unknown
	 */
	protected function EventGo() {
		/**
		 * Получаем номер топика из УРЛ и проверяем существует ли он
		 */
		$sTopicId=$this->GetParam(0);
		if (!$oTopic=$this->Topic_GetTopicById($sTopicId,$this->oUserCurrent)) {
			return parent::EventNotFound();
		}
		/**
		 * проверяем является ли топик ссылкой
		 */
		if ($oTopic->getType()!='link') {
			return parent::EventNotFound();
		}
		/**
		 * увелививаем число переходов по ссылке
		 */
		$oTopic->setLinkCountJump($oTopic->getLinkCountJump()+1);
		$this->Topic_UpdateTopic($oTopic);
		/**
		 * собственно сам переход по ссылке
		 */
		func_header_location($oTopic->getLinkUrl());
	}
	
	
	/**
	 * Редактирование ссылки
	 *
	 * @return unknown
	 */
	protected function EventEdit() {
		/**
		 * Проверяем авторизован ли юзер
		 */
		if (!$this->User_IsAuthorization()) {
			$this->Message_AddErrorSingle('Для того чтобы что то написать, сначало нужно войти под своим аккаунтом.','Нет доступа');
			return Router::Action('error'); 
		}
		/**
		 * Меню
		 */
		$this->sMenuSubItemSelect='';
		$this->sMenuItemSelect='link';
		/**
		 * Получаем номер топика из УРЛ и проверяем существует ли он
		 */
		$sTopicId=$this->GetParam(0);
		if (!$oTopic=$this->Topic_GetTopicById($sTopicId,$this->oUserCurrent)) {
			return parent::EventNotFound();
		}
		/**
		 * проверяем кто владелец топика
		 */
		if ($oTopic->getUserId()!=$this->oUserCurrent->getId()) {
			return parent::EventNotFound();
		}
		/**
		 * Добавляем блок вывода информации о блоге
		 */
		$this->Viewer_AddBlocksRight(array('block.blogInfo.tpl'));
		/**
		 * Получаем данные для отображения формы
		 */
		$aBlogsOwner=$this->Blog_GetBlogsByOwnerId($this->oUserCurrent->getId());		
		$aBlogsUser=$this->Blog_GetRelationBlogUsersByUserId($this->oUserCurrent->getId());
		$aAllowBlogsUser=array();
		foreach ($aBlogsUser as $oBlogUser) {
			$oBlog=$this->Blog_GetBlogById($oBlogUser->getBlogId());
			// делаем через "or" чтоб дать возможность юзеру отредактировать свой топик в блоге в котором он уже не может постить, т.е. для тех топиков что были запощены раньше и был доступ в блог
			if ($this->ACL_CanAddTopic($this->oUserCurrent,$oBlog) or $oTopic->getBlogId()==$oBlog->getId()) {
				$aAllowBlogsUser[]=$oBlogUser;
			}
		}
		/**
		 * Загружаем переменные в шаблон
		 */
		$this->Viewer_Assign('aBlogsUser',$aAllowBlogsUser);
		$this->Viewer_Assign('aBlogsOwner',$aBlogsOwner);
		$this->Viewer_AddHtmlTitle('Редактирование ссылки');
		/**
		 * Устанавливаем шаблон вывода
		 */
		$this->SetTemplateAction('add');		
		/**
		 * Проверяем отправлена ли форма с данными(хотяб одна кнопка)
		 */		
		if (isset($_REQUEST['submit_topic_publish']) or isset($_REQUEST['submit_topic_save'])) {
			/**
		 	* Обрабатываем отправку формы
		 	*/
			return $this->SubmitEdit($oTopic);
		} else {
			/**
		 	* Заполняем поля формы для редактирования
		 	* Только перед отправкой формы!
		 	*/
			$_REQUEST['topic_title']=$oTopic->getTitle();
			$_REQUEST['topic_link_url']=$oTopic->getLinkUrl();
			$_REQUEST['topic_text']=$oTopic->getTextSource();
			$_REQUEST['topic_tags']=$oTopic->getTags();
			$_REQUEST['blog_id']=$oTopic->getBlogId();
			$_REQUEST['topic_id']=$oTopic->getId();
		}	
	}
	/**
	 * Добавление ссылки
	 *
	 * @return unknown
	 */
	protected function EventAdd() {
		/**
		 * Проверяем авторизован ли юзер
		 */
		if (!$this->User_IsAuthorization()) {
			$this->Message_AddErrorSingle('Для того чтобы что то написать, сначало нужно войти под своим аккаунтом.','Нет доступа');
			return Router::Action('error'); 
		}
		/**
		 * Меню
		 */
		$this->sMenuSubItemSelect='add';
		/**
		 * Добавляем блок вывода информации о блоге
		 */		
		$this->Viewer_AddBlocksRight(array('block.blogInfo.tpl'));
		/**
		 * Получаем данные для отображения формы
		 */
		$aBlogsOwner=$this->Blog_GetBlogsByOwnerId($this->oUserCurrent->getId());		
		$aBlogsUser=$this->Blog_GetRelationBlogUsersByUserId($this->oUserCurrent->getId());		
		$aAllowBlogsUser=array();
		foreach ($aBlogsUser as $oBlogUser) {
			$oBlog=$this->Blog_GetBlogById($oBlogUser->getBlogId());
			if ($this->ACL_CanAddTopic($this->oUserCurrent,$oBlog)) {
				$aAllowBlogsUser[]=$oBlogUser;
			}
		}
		/**
		 * Загружаем переменные в шаблон
		 */
		$this->Viewer_Assign('aBlogsUser',$aAllowBlogsUser);
		$this->Viewer_Assign('aBlogsOwner',$aBlogsOwner);		
		$this->Viewer_AddHtmlTitle('Добавление ссылки');
		/**
		 * Обрабатываем отправку формы
		 */
		return $this->SubmitAdd();		
	}
	
	/**
	 * Обработка добавлени топика
	 *
	 * @return unknown
	 */
	protected function SubmitAdd() {
		/**
		 * Проверяем отправлена ли форма с данными(хотяб одна кнопка)
		 */		
		if (!isset($_REQUEST['submit_topic_publish']) and !isset($_REQUEST['submit_topic_save'])) {
			return false;
		}		
		/**
		 * Проверка корректности полей формы
		 */
		if (!$this->checkTopicFields()) {
			return false;	
		}		
		/**
		 * Определяем в какой блог делаем запись
		 */
		$iBlogId=getRequest('blog_id');	
		if ($iBlogId==0) {
			$oBlog=$this->Blog_GetPersonalBlogByUser($this->oUserCurrent);
		} else {
			$oBlog=$this->Blog_GetBlogById($iBlogId);
		}	
		/**
		 * Если блог не определен выдаем предупреждение
		 */
		if (!$oBlog) {
			$this->Message_AddErrorSingle('Пытаетесь запостить топик в неизвестный блог?','Ошибка');
			return false;
		}		
		/**
		 * Проверка состоит ли юзер в блоге в который постит
		 */
		if (!$this->Blog_GetRelationBlogUserByBlogIdAndUserId($oBlog->getId(),$this->oUserCurrent->getId())) {
			if ($oBlog->getOwnerId()!=$this->oUserCurrent->getId()) {
				$this->Message_AddErrorSingle('Вы не состоите в этом блоге!','Ошибка');
				return false;
			}
		}		
		/**
		 * Проверяем есть ли права на постинг топика в этот блог
		 */
		if (!$this->ACL_CanAddTopic($this->User_GetUserCurrent(),$oBlog)) {
			$this->Message_AddErrorSingle('Вы еще не достаточно окрепли чтобы постить в этот блог','Ошибка');
			return false;
		}						
		/**
		 * Теперь можно смело добавлять топик к блогу
		 */
		$oTopic=new TopicEntity_Topic();
		$oTopic->setBlogId($oBlog->getId());
		$oTopic->setUserId($this->oUserCurrent->getId());
		$oTopic->setType('link');
		$oTopic->setTitle(getRequest('topic_title'));								
		$oTopic->setText(htmlspecialchars(getRequest('topic_text')));
		$oTopic->setTextShort(htmlspecialchars(getRequest('topic_text')));
		$oTopic->setTextSource(getRequest('topic_text'));
		$oTopic->setLinkUrl(getRequest('topic_link_url'));		
		$oTopic->setTags(getRequest('topic_tags'));
		$oTopic->setDateAdd(date("Y-m-d H:i:s"));
		$oTopic->setUserIp(func_getIp());
		/**
		 * Публикуем или сохраняем
		 */
		if (isset($_REQUEST['submit_topic_publish'])) {
			$oTopic->setPublish(1);
		} else {
			$oTopic->setPublish(0);
		}				
		/**
		 * Добавляем топик
		 */
		if ($this->Topic_AddTopic($oTopic)) {
			//Делаем рассылку спама всем, кто состоит в этом блоге
			if ($oTopic->getPublish()==1 and $oBlog->getType()!='personal') {
				$aBlogUsers=$this->Blog_GetRelationBlogUsersByBlogId($oBlog->getId());
				foreach ($aBlogUsers as $oBlogUser) {
					if ($oBlogUser->getUserId()==$this->oUserCurrent->getId()) {
						continue;
					}
					$this->Mail_SetAdress($oBlogUser->getUserMail(),$oBlogUser->getUserLogin());
					$this->Mail_SetSubject('Новый топик в блоге «'.htmlspecialchars($oBlog->getTitle()).'»');
					$this->Mail_SetBody('
							В блоге <b>«'.htmlspecialchars($oBlog->getTitle()).'»</b> опубликован топик -  <a href="'.DIR_WEB_ROOT.'/blog/'.$oTopic->getId().'.html">'.htmlspecialchars($oTopic->getTitle()).'</a><br>						
														
							<br>
							С уважением, администрация сайта <a href="'.DIR_WEB_ROOT.'">'.SITE_NAME.'</a>
						');
					$this->Mail_setHTML();
					$this->Mail_Send();
				}
			}
			
			func_header_location(DIR_WEB_ROOT.'/blog/'.$oTopic->getId().'.html');
		} else {
			$this->Message_AddErrorSingle('Возникли технические неполадки при добавлении топика, пожалуйста повторите позже.','Внутреняя ошибка');
			return Router::Action('error');
		}		
	}
	/**
	 * Обработка редактирования топика
	 *
	 * @param unknown_type $oTopic
	 * @return unknown
	 */
	protected function SubmitEdit($oTopic) {				
		/**
		 * Проверка корректности полей формы
		 */
		if (!$this->checkTopicFields()) {
			return false;	
		}		
		/**
		 * Определяем в какой блог делаем запись
		 */
		$iBlogId=getRequest('blog_id');	
		if ($iBlogId==0) {
			$oBlog=$this->Blog_GetPersonalBlogByUser($this->oUserCurrent);
		} else {
			$oBlog=$this->Blog_GetBlogById($iBlogId);
		}	
		/**
		 * Если блог не определен выдаем предупреждение
		 */
		if (!$oBlog) {
			$this->Message_AddErrorSingle('Пытаетесь запостить топик в неизвестный блог?','Ошибка');
			return false;
		}			
		/**
		 * Проверка состоит ли юзер в блоге в который постит
		 * Если нужно разрешить редактировать топик в блоге в котором юзер уже не стоит
		 */
		if (!$this->Blog_GetRelationBlogUserByBlogIdAndUserId($oBlog->getId(),$this->oUserCurrent->getId())) {
			if ($oBlog->getOwnerId()!=$this->oUserCurrent->getId()) {
				$this->Message_AddErrorSingle('Вы не сотоите в этом блоге!','Ошибка');
				return false;
			}
		}		
		/**
		 * Проверяем есть ли права на постинг топика в этот блог
		 * Условие $oBlog->getId()!=$oTopic->getBlogId()  для того чтоб разрешить отредактировать топик в блоге в который сейчас юзер не имеет права на постинг, но раньше успел в него запостить этот топик
		 */
		if (!$this->ACL_CanAddTopic($this->User_GetUserCurrent(),$oBlog) and $oBlog->getId()!=$oTopic->getBlogId()) {
			$this->Message_AddErrorSingle('Вы еще не достаточно окрепли чтобы постить в этот блог','Ошибка');
			return false;
		}						
		/**
		 * Теперь можно смело редактировать топик
		 */		
		$oTopic->setBlogId($oBlog->getId());		
		$oTopic->setTitle(getRequest('topic_title'));			
		$oTopic->setText(htmlspecialchars(getRequest('topic_text')));
		$oTopic->setTextShort(htmlspecialchars(getRequest('topic_text')));
		$oTopic->setTextSource(getRequest('topic_text'));
		$oTopic->setLinkUrl(getRequest('topic_link_url'));
		$oTopic->setTags(getRequest('topic_tags'));		
		$oTopic->setUserIp(func_getIp());
		/**
		 * Публикуем или сохраняем в черновиках
		 */
		if (isset($_REQUEST['submit_topic_publish'])) {
			$oTopic->setPublish(1);
		} else {
			$oTopic->setPublish(0);
		}				
		/**
		 * Сохраняем топик
		 */
		if ($this->Topic_UpdateTopic($oTopic)) {
			func_header_location(DIR_WEB_ROOT.'/blog/'.$oTopic->getId().'.html');
		} else {
			$this->Message_AddErrorSingle('Возникли технические неполадки при изменении топика, пожалуйста повторите позже.','Внутреняя ошибка');
			return Router::Action('error');
		}		
	}
	/**
	 * Проверка полей формы
	 *
	 * @return unknown
	 */
	protected function checkTopicFields() {
		$bOk=true;
		/**
		 * Проверяем есть ли блог в кторый постим
		 */
		if (!func_check(getRequest('blog_id'),'id')) {
			$this->Message_AddError('Что то не то с блогом..','Ошибка');
			$bOk=false;
		}
		/**
		 * Проверяем есть ли заголовок топика
		 */
		if (!func_check(getRequest('topic_title'),'text',2,200)) {
			$this->Message_AddError('Название топика должно быть от 2 до 200 символов','Ошибка');
			$bOk=false;
		}
		/**
		 * Проверяем есть ли ссылка
		 */
		if (!func_check(getRequest('topic_link_url'),'text',3,200)) {
			$this->Message_AddError('Название топика должно быть от 2 до 200 символов','Ошибка');
			$bOk=false;
		}
		/**
		 * Проверяем есть ли описание топика-ссылки
		 */
		if (!func_check(getRequest('topic_text'),'text',10,500)) {
			$this->Message_AddError('Описание ссылки должно быть от 10 до 500 символов','Ошибка');
			$bOk=false;
		}
		/**
		 * Проверяем есть ли теги(метки)
		 */
		if (!func_check(getRequest('topic_tags'),'text',2,500)) {
			$this->Message_AddError('Метки топика должны быть от 2 до 50 символов с общей диной не более 500 символов','Ошибка');
			$bOk=false;
		}
		/**
		 * проверяем ввод тегов 
		 */
		$sTags=getRequest('topic_tags');
		$aTags=explode(',',$sTags);
		$aTagsNew=array();
		foreach ($aTags as $sTag) {
			$sTag=trim($sTag);
			if (func_check($sTag,'text',2,50)) {
				$aTagsNew[]=$sTag;
			}
		}
		if (!count($aTagsNew)) {
			$this->Message_AddError('Проверьте правильность меток','Ошибка');
			$bOk=false;
		} else {
			$_REQUEST['topic_tags']=join(',',$aTagsNew);
		}
		return $bOk;
	}
	/**
	 * При завершении экшена загружаем необходимые переменные
	 *
	 */
	public function EventShutdown() {
		$this->Viewer_Assign('sMenuItemSelect',$this->sMenuItemSelect);
		$this->Viewer_Assign('sMenuSubItemSelect',$this->sMenuSubItemSelect);		
	}
}
?>