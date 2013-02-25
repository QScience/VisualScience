var vsMessage = (function() {
	var createAttachmentsDiv, createRecipientsDiv, insertEmailIntoRecipientsDiv, getRecipientsOfMessage, renameMessageTab;
	/*
	 * The attachment div for messages and conferences
	 */
	 createAttachmentsDiv = function(thisTabId) {
	 	var content = '<div id="visualscience-message-attachments-div-show-' + thisTabId + '" style="height:150px;overflow-y:scroll;"></div><div id="upload-form-' + thisTabId + '"></div> <div id="progress-upload-' + thisTabId + '" style="margin:5px;padding:5px;background-color:red;font-size:12px;display:none;" >Progress</div>';
	 	return '<div id="visualscience-attachments-div-' + thisTabId + '" style="display:inline-block;width:100%;border:solid black 1px;margin-top:20px;">' + content + '</div>';
	 };
	/*
	 * The recipients div for messages and conferences
	 */
	 createRecipientsDiv = function(thisTabId, selectedUsers, selectedUsersEmail) {
	 	var recipientsLayout = vsInterface.getView('msgRecipientsLayout.html');
	 	var users = new Array();
	 	for (var i = 0; i < selectedUsers.length; i++) {
	 		users.push({id: i, email: selectedUsersEmail[i], name: selectedUsers[i], tab:thisTabId});//Have to put tab, otherwise not well interpreted into handlebars' view
	 	}
	 	var parameters = {
	 		thisTabId: thisTabId,
	 		nbUsers: selectedUsers.length,
	 		user: users
	 	};
	 	return recipientsLayout(parameters);
	 };

	 createMessageEditor = function (thisTabId) {
	 	var messageEditor = vsInterface.getView('msgTabLayout.html');
	 	var parameters = {
	 		thisTabId: thisTabId
	 	};
	 	return messageEditor(parameters);
	 };

	 insertEmailIntoRecipientsDiv = function(thisTabId, email, nbRecipients) {
	 	nbRecipients += 1;
	 	var newEntry = vsInterface.getView('msgNewRecipientsEntry.html');
	 	var parameters = {
	 		thisTabId: thisTabId,
	 		email: email,
	 		nbRecipients: nbRecipients
	 	};
	 	jQuery('#visualscience-recipient-div-content-' + thisTabId).append(newEntry(parameters));
	 };
	/*
	 * Gets the name and email of every recipients of a message.
	 */
	 getRecipientsOfMessage = function(thisTabId) {
	 	var recipientsEmailAndName = new Array();
	 	jQuery('p[id*="visualscience-recipients-entry-' + thisTabId + '"]').each(function(i) {
	 		recipientsEmailAndName[i] = new Array(2);
	 		recipientsEmailAndName[i][0] = jQuery(this).children(':nth-child(2)').text();
	 		recipientsEmailAndName[i][1] = jQuery(this).children(':nth-child(2)').attr('href').substring(7);
	 	});
	 	return recipientsEmailAndName;
	 };

	 renameMessageTab =  function (thisTabId) {
	 	var nbRecipients = jQuery('#visualscience-recipient-div-content-'+thisTabId+' p').size();
	 	var title = '';
	 	if (nbRecipients == 1) {
	 		title = ' ' + jQuery('#visualscience-recipient-div-content-'+thisTabId+' p a:nth-child(2)').text();
	 	}
	 	else if (nbRecipients == 0) {
	 		title = ' No User';
	 	}
	 	else {
	 		title = ' ' + nbRecipients + ' Users';
	 	}
	 	var oldTitle = jQuery('a[href="#message-tab-' + thisTabId + '"]').text();
	 	oldTitle = oldTitle.substring(0, oldTitle.length -1);
	 	var tabTitleContent = jQuery('a[href="#message-tab-' + thisTabId + '"]').html().replace(oldTitle, title);
	 	jQuery('a[href="#message-tab-' + thisTabId + '"]').html(tabTitleContent);
	 };
	 return {
		/*
		 * This function creates a new Tab where it is possible to send a message to the selected user(s)
		 */
		 createTabSendMessage : function(idOfTheTab) {
		 	selectedUsers = vsSearch.getSelectedUsersFromSearchTable(idOfTheTab);
		 	if (selectedUsers.length > 0) {
		 		var selectedUsersEmail = vsSearch.getSelectedUsersEmailFromSearchTable(idOfTheTab);
		 		var title = vsUtils.getTitleFromUsers(selectedUsers);
		 		var thisTabId = vsInterface.getTabId();
		 		vsInterface.addTab('<img src="' + vsUtils.getInstallFolder() + '/images/message.png" width="13px" alt="image for message tab" /> ', title, '#message-tab-' + thisTabId);

				//Create the message tab's HTML
				//var attachmentDiv = createAttachmentsDiv(thisTabId);
				var recipientsDiv = createRecipientsDiv(thisTabId, selectedUsers, selectedUsersEmail);


				var msgTabLayout = vsInterface.getView('msgTabLayout.html');
				var parameters = {
					recipientsDiv: recipientsDiv,
					thisTabId: thisTabId
				};
				var messageTab = msgTabLayout(parameters);

				//var msgEditor = createMessageEditor(thisTabId);
				//var messageTab = '<h3>Message</h3><div width="100%">'+msgEditor+'<div style="float:right;width:45%;display:inline-block;">' + recipientsDiv + attachmentDiv + '</div></div>';
				jQuery('#message-tab-' + thisTabId).html(messageTab);
				vsUtils.loadCLEditor('visualscience-message-input-' + thisTabId);
				vsUtils.loadDrupalHTMLUploadForm('no', 'upload-form-' + thisTabId, thisTabId);
				vsUtils.loadUploadScripts('upload-button-' + thisTabId, function() {
					//addAttachments();
				});
			} else {
				alert('Please select at least one user.');
			}
		},
		/*
		 * Get informations and send them to the server through ajax
		 */
		 sendVisualscienceMessage : function(thisTabId) {
		 	var mailURL = vsUtils.getSendMailURL();
		 	jQuery('#visualscience-send-message-button-' + thisTabId).attr({
		 		'value' : 'Sending Message... Please wait',
		 		'disabled' : 'disabled'
		 	});
		 	var subjectVal = jQuery('#visualscience-subject-input-' + thisTabId).val();
		 	var messageVal = jQuery('#visualscience-message-input-' + thisTabId).val();
		 	var attachmentJson = vsUtils.getJsonOfAttachments(thisTabId);
		 	var recipientsArray = getRecipientsOfMessage(thisTabId);
		 	var flagAllDone = false;
		 	if (recipientsArray.length < 1) {
		 		alert('Please insert at least one recipient.');
		 		jQuery('#visualscience-send-message-button-' + thisTabId).attr({
		 			'value' : 'Send Message',
		 			'disabled' : false
		 		});
		 		return false;
		 	}
		 	for (var i = 0; i < recipientsArray.length; i++) {
		 		var recipientsVal = {
		 			name : recipientsArray[i][0],
		 			email : recipientsArray[i][1]
		 		};
		 		var jsonObject = {
		 			subject : subjectVal,
		 			message : messageVal,
		 			recipients : recipientsVal,
		 			attachments : attachmentJson
		 		};
		 		jQuery.ajax({
		 			url : mailURL,
		 			type : 'POST',
		 			data : jsonObject,
		 			error : function(req, msg, obj) {
		 				alert('An error occured on the server side while sending the message. Please contact the administrator if this happens again.');
		 				console.log(req);
		 				console.log(msg);
		 				console.log(obj);
		 				jQuery('#visualscience-send-message-button-' + thisTabId).attr({
		 					'value' : 'Re-try now',
		 					'disabled' : false
		 				});
		 			},
		 			success : function(data) {
		 				if (parseInt(data) != 1) {
		 					alert('There was a problem while sending the email. Please try again later.');
		 					jQuery('#visualscience-send-message-button-' + thisTabId).attr({
		 						'value' : 'Re-try now',
		 						'disabled' : false
		 					});
		 				}
		 			}
		 		});
		 		if (i == recipientsArray.length - 1) {
		 			flagAllDone = true;
		 		}
		 	}
			while (!flagAllDone);//Barrier to wait until all the requests has been made
			jQuery('#visualscience-send-message-button-' + thisTabId).attr({
				'value' : 'Message Sent. Send again ?',
				'disabled' : false
			});
		},
		/*
		 * Gets the value of the email to add and insert it into the div
		 */
		 addRecipientForMessage : function(thisTabId) {
		 	var email = jQuery('#visualscience-message-add-recipient-email-' + thisTabId).val();
		 	if (email.indexOf('@') != -1) {
		 		var nbRecipients = parseInt(jQuery('#visualscience-message-add-recipient-button-' + thisTabId).attr('nbRecipients'));
		 		insertEmailIntoRecipientsDiv(thisTabId, email, nbRecipients);
		 		jQuery('#visualscience-message-add-recipient-button-' + thisTabId).attr('nbRecipients', nbRecipients + 1);
		 		renameMessageTab(thisTabId);
		 		jQuery('#visualscience-recipient-div-content-' + thisTabId).scrollTop(jQuery('#visualscience-recipient-div-content-'+thisTabId)[0].scrollHeight);
		 	} else {
		 		alert('Please enter a valid email');
		 	}
		 },
		 deleteRecipientToMessage : function(thisTabId, entryNb) {
		 	jQuery('#visualscience-recipients-entry-' + thisTabId + '-' + entryNb).hide(350, function() {
		 		jQuery('#visualscience-recipients-entry-' + thisTabId + '-' + entryNb).remove();
		 		renameMessageTab(thisTabId);
		 	});
		 }
		};

	})();
