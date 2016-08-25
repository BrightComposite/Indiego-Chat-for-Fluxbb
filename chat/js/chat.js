/**
 *  @package    IndieGo Chat
 *  @file       chat.js - Обрабатывает пользовательский ввод и выполняет запросы к серверу
 *
 *  @version    1.0
 *  @date       2015-06-08
 *  @author     IndieGo (indiego.mttt@gmail.com)
 *  @sponsor    Volkula (volkula@gmail.com)
 *  
 *  @copyright  Copyright (c) IndieGo, 2015
 *  
 */

//(function($, undefined)
//{
    var w = $(window);
    
    /** @type Chat */
    var chat = null;
    
    /** @type ChatToolbar */
    var toolbar = null;
    
    const SEND_TIMEOUT = 2000;
    const WARNING_DURATION = 4000;
    
    const SOUNDS = {
        NEW: "chat/sounds/new.ogg",
        SEND: "chat/sounds/send.ogg",
        WARNING: "chat/sounds/warning.ogg"
    };
    
    const TOOLBAR_STATE = {
        INITIAL: 0,  
        EDIT: 1
    };
    
    const SOUND_MODE = {
        ENABLED: 'Звук: вкл',
        DISABLED: 'Звук: выкл'
    };
 
    const AUTOSCROLL_MODE = {
        ENABLED: 'Авто-прокрутка: вкл',
        DISABLED: 'Авто-прокрутка: выкл'
    };
 
    const SMILIES = [
        [':)', 'img/smilies/smile.png'],
        [':(', 'img/smilies/sad.png'],
        [':)', 'img/smilies/smile.png'],
        [':)', 'img/smilies/smile.png'],
        [':)', 'img/smilies/smile.png'],
        [':)', 'img/smilies/smile.png'],
        [':)', 'img/smilies/smile.png'],
        [':)', 'img/smilies/smile.png']
    ];
    
    function formatTimeComponent(t)
    {
        return (t < 10 ? '0' : '') + t;
    }
    
    function replaceSelected(jqelem, callback)
    {
        jqelem.focus();
     
        if(document.selection) 
        {
            var s = document.selection.createRange();
            
            if(s.text)
            {
                s.text = callback(s.text);
                s.select();
                return true;
            }
        }
        else if(typeof(jqelem[0].selectionStart) == "number")
        {
            if(jqelem[0].selectionStart != jqelem[0].selectionEnd)
            {
                var start = jqelem[0].selectionStart;
                var end = jqelem[0].selectionEnd;
                
                var s = jqelem.val().substr(start, end - start);
                var oldl = s.length;
                var rs = callback(s);
                jqelem.val(jqelem.val().substr(0, start) + rs + jqelem.val().substr(end));
                end += rs.length - oldl;
                jqelem[0].setSelectionRange(end, end);
                
                return true;
            }
        }
    
        return false;
    }
    
    function moveCaret(jqelem, pos)
    {
        jqelem.focus();
        
        if(jqelem[0].createTextRange)
        {
            var rng = jqelem[0].createTextRange();
            rng.collapse()
            rng.moveStart("character", pos);
            rng.select();
            
            return;
        }
        
        jqelem[0].setSelectionRange(pos, pos);
        return;
    }
//  class Chat
//  {
        function Chat()
        {
            chat = this;
            this.element = $("#indiego-chat .contents");
            this.sound = $("#indiego-chat #sound");
            this.sound[0].volume = 0.3;
            this.soundEnabled = true;
            this.autoscrollEnabled = true;
            
            this.element.on('dblclick', '.nick', function(event)
            {
                if(event.button != 0)
                    return;
                
                var
                    str = toolbar.input.val(),
                    nick = $(this).html();
                
                str += (str.length > 0) ? (' ' + nick + ' ') : (nick + ': ');
                
                toolbar.input.val(str);
            });
            
            this.element.on('click', '.edit_msg', function(event)
            {
                if(event.button != 0)
                    return;
                
                chat.selectedMessage = $(this).parent();
                toolbar.setState(TOOLBAR_STATE.EDIT);
            });
            
            this.element.on('click', '.delete_msg', function(event)
            {
                if(event.button != 0)
                    return;
                
                chat.selectedMessage = $(this).parent();
                chat.deleteMessage();
            });
            
            this.userId = this.extractInput('user_id');
            this.maxMessages = this.extractInput('max_msgs');
            this.updatePeriod = this.extractInput('update_period') * 1000;
            
            this.requestUserData();
            this.requestMessages();
            
            this.updateTimer = setInterval(function()
            {
                chat.update();
            }, this.updatePeriod);
        }
        
        Chat.prototype =
        {
            userData: [],
            messages: [],
            history: [],
            warnings: [],
            selectedMessage: null,
            refreshTimer: null,
            sendTimeout: false,
            adminMode: false,
            historyDepth: -1,
            
            extractInput: function(id)
            {
                var
                    element = $('#' + id),
                    value = element.val();
                    
                element.remove();
                return value;
            },
            
            request: function(type, callback, data)
            {
                var
                    requestData = {user_id: this.userId, request: type};
                
                if(typeof callback !== 'function')
                {
                    data = callback;
                    callback = null;
                }
                
                if(data !== undefined && typeof data === 'object')
                    $.extend(requestData, data);
                    
                $.getJSON('./chat/ajax.php', requestData)
                    .done(function(response, status)
                    {
                        if(response.error != undefined)
                        {
                            console.log(response.error);
                            return;
                        }
                    
                        //console.log('Successful request: ' + type);
                        
                        if(!!callback)
                            callback(response);
                    })
                    .fail(function(error)
                    {
                        console.log("Error while making request: " + type);
                        console.log(error.responseText);
                    });
            },
            
            requestUserData: function()
            {
                this.request('userdata', function(response)
                {
                    chat.userData = response;
                    
                    if(chat.userData.role == 'admin')
                    {
                        chat.adminMode = true;
                        chat.updateMessages();
                    }
                });
            },
            
            requestMessages: function()
            {
                this.request('messages', function(response)
                {
                    chat.messages = response;
                    chat.updateMessages();
                });
            },
            
            update: function()
            {
                this.request('update', function(response)
                {
                    var
                        changed = false,
                        newmsg = false;
                        
                    for(var i = 0; i < response.length; ++i)
                    {
                        var command = response[i];
                        
                        switch(command.code)
                        {
                            case 'am':
                                changed = true;
                                
                                if(command.message.user_id != chat.userData.id)
                                    newmsg = true;
                                    
                                chat.messages.push(command.message);
                                break;
                            case 'um':
                                var index = chat.findIndex(command.message.id);
                                
                                if(index > 0)
                                {
                                    chat.messages[index] = command.message;
                                    changed = true;
                                }
                                    
                                break;
                            case 'dm':
                                var index = chat.findIndex(command.msg_id);
                                
                                if(index > 0)
                                {
                                    chat.messages.splice(index, 1);
                                    changed = true;
                                }
                                
                                break;
                        }
                    }
                    
                    if(changed)
                        chat.updateMessages();
                        
                    if(newmsg)
                        chat.playSound(SOUNDS.NEW);
                });
            },
            
            findIndex: function(id)
            {
                for(var i = 0; i < this.messages.length; ++i)
                    if(this.messages[i].id == id)
                       return i;
                
                return -1;
            },
            
            updateMessages: function()
            {
                this.element.empty();
                
                while(this.messages.length > this.maxMessages)
                    this.messages.shift();
                
                for(var i = 0; i < this.messages.length; ++i)
                {
                    this.element.append(this.createMessageElement(this.messages[i]));
                }
                
                for(var i = 0; i < this.warnings.length; ++i)
                    this.element.append(this.warnings[i]);
                    
                if(this.autoscrollEnabled)
                    this.element.scrollTop(9999);
            },
            
            warning: function(text)
            {
                chat.warnings.push($('<div class="message warning">' + text + '</div>'));
                chat.updateMessages();
                chat.playSound(SOUNDS.WARNING);
                
                setTimeout(function()
                {
                    chat.warnings.shift();
                    chat.updateMessages();
                }, WARNING_DURATION);
            },
            
            createMessageElement: function(msg)
            {
                var
                    date_time = msg.time.split('|'),
                    date = date_time[0].split('.'),
                    time = date_time[1].split(':'),
                    day = parseInt(date[0]),
                    month = parseInt(date[1]),
                    year = parseInt(date[2]),
                    hours = parseInt(time[0]),
                    minutes = parseInt(time[1]),
                    seconds = parseInt(time[2]),
                    d = new Date();
                     
                d.setUTCDate(day);
                d.setUTCMonth(month);
                d.setUTCFullYear(year);
                d.setUTCHours(hours);
                d.setUTCMinutes(minutes);
                d.setUTCSeconds(seconds);
                
                day = d.getDate();
                month = d.getMonth();
                year = d.getFullYear();
                hours = d.getHours();
                minutes = d.getMinutes();
                seconds = d.getSeconds();
                
                var
                    str = '',
                    today = new Date();
                    
                if(today.getDate() != day || today.getMonth() + 1 != month || today.getFullYear() != year)
                {
                    str += formatTimeComponent(day) + '.';
                    str += formatTimeComponent(month) + '.';
                    str += year + ' - ';
                }
                
                str += formatTimeComponent(hours) + ':';
                str += formatTimeComponent(minutes) + ':';
                str += formatTimeComponent(seconds);

                return $(this.messageHtml(msg, str));
            },
            
            playSound: function(sound)
            {
                if(!chat.soundEnabled)
                {
                    if(!this.sound[0].paused)
                    {
                        this.sound[0].pause();
                        this.sound[0].src = "";
                    }
                    
                    return;
                }
                
                if(!this.sound[0].paused)
                    this.sound[0].pause();
                    
                this.sound[0].src = sound;
                this.sound[0].play();
            },
            
            processMessageText: function(msg)
            {
                if(this.userData.name != null)
                    msg = msg.replace(this.userData.name, '<span class="ownnick">' + chat.userData.name + '</span>');
                
                return msg;
            },
            
            messageHtml: function(msg, time)
            {
                var
                    str = '<div id="msg' + msg.id + '" class="message">';
                    
                str += '(' + time + ') ';
                str += '<span class="nick ' + msg.user_role + (this.userData.name == msg.user_name ? ' ownnick' : '') + '">' + msg.user_name + '</span>: ';
                str += '<span class="msg_text">' + this.processMessageText(msg.text) + '</span>';
                
                if(this.adminMode)
                {
                    str += '<span class="delete_msg" title="Удалить сообщение"></span>';
                    str += '<span class="edit_msg"   title="Редактировать сообщение"></span>';
                }
                
                str += '</div>';
                
                return str;
            },
            
            flushInput: function()
            {
                var text = toolbar.input.val();
                toolbar.input.val('');
                
                return text;
            },
            
            sendMessage: function(text)
            {
                if(text.length == 0)
                    return;
                
                if(this.sendTimeout)
                {
                    this.warning('Вы отправляете сообщения слишком часто! Таймаут после отправки: ' + SEND_TIMEOUT / 1000 + ' секунд');
                    return;
                }
                
                if(!this.adminMode)
                {
                    this.sendTimeout = true;
                    
                    setTimeout(function()
                    {
                        chat.sendTimeout = false;
                    }, SEND_TIMEOUT);
                }
                
                this.warnings = [];
                this.historyDepth = -1;
                this.history.push(text);
                this.request('addmessage', {message: text});
                this.update();
                this.playSound(SOUNDS.SEND);
            },
            
            editMessage: function(text)
            {
                if(!this.adminMode || this.selectedMessage == null)
                    return;
                
                var
                    id = this.getSelectedMessageId(),
                    index = this.findIndex(id);
                    
                this.request('editmessage', {msg_id: id, message: text});
                this.messages[index].text = text;
                this.updateMessages();
            },
            
            deleteMessage: function()
            {
                if(!this.adminMode || this.selectedMessage == null)
                    return;
                
                var
                    id = this.getSelectedMessageId(),
                    index = this.findIndex(id);
                    
                this.request('deletemessage', {msg_id: id});
                this.messages.splice(index, 1);
                this.updateMessages();
            },
            
            undo: function()
            {
                var length = this.history.length;
                
                if(this.historyDepth >= length - 1)
                    return;
                
                ++this.historyDepth;
                toolbar.input.val(this.history[length - 1 - this.historyDepth]);
            },
            
            redo: function()
            {
                if(this.historyDepth < 0)
                    return;
                
                var length = this.history.length;
                --this.historyDepth;
                
                if(this.historyDepth >= 0)
                {
                    toolbar.input.val(this.history[length - 1 - this.historyDepth]);
                    return;
                }
                
                toolbar.input.val('');
            },
            
            getSelectedMessageId: function()
            {
                return parseInt(this.selectedMessage.attr('id').replace('msg', ''));
            },
            
            getSelectedMessageText: function()
            {
                return this.selectedMessage.children(".msg_text").html();
            }
        }
//  }
 
//  class ChatToolbar
//  {
        function ChatToolbar()
        {
            toolbar = this;
            
            this.input = $("#indiego-chat #message-input");
            this.sender = $("#indiego-chat #message-sender");
            this.menus = $("#indiego-chat .chat-control");
            
            this.currentMenu = null;
            
            this.menus.append($('<div class="back"></div>'));
            this.menus.append($('<div class="image"></div>'));
            
            this.menus.children(".image, .back").is()
            
            this.menus.on('click', function(event)
            {
                event.preventDefault();
                event.stopPropagation();
                
                if(toolbar.currentMenu != null && toolbar.currentMenu[0] == this)
                {
                    if(toolbar.currentMenu.children(".image, .back").is(event.target))
                        toolbar.switchCurrentMenu(null);
                        
                    return false;
                }
                
                toolbar.switchCurrentMenu($(this));
                return false;
            });
            
            this.menus.filter('#indiego-chat-smiles, #indiego-chat-bb, #indiego-chat-colors').on('click', function(event)
            {
                toolbar.input.focus();
            });
            
            $(window).on('click', function(event)
            {
                toolbar.switchCurrentMenu(null);
            });
            
            this.panels = this.menus.children(".panel");
            
            this.smiles = $("#indiego-chat-smiles");
            this.settings = $("#indiego-chat-settings");
            this.help = $("#indiego-chat-help");
            this.settingsPanel = this.settings.children(".panel");
            
            this.senderCaption = this.sender.val();
            this.senderCallback = null;
            
            this.cancel = this.sender.clone(false);
            this.cancel.val('Отменить');
            this.cancel.attr('id', 'message-cancel');
            this.cancel.appendTo(this.sender.parent());
            this.cancel.hide();
            
            this.smileInserters = $(".smile-inserter");
            this.bbInserters = $(".bb-inserter");
            this.colorInserters = $(".color-inserter");
            
            this.soundToggle = $("#indiego-chat #sound-toggle");
            this.soundToggle.val(SOUND_MODE.ENABLED);
            
            this.autoscrollToggle = $("#indiego-chat #autoscroll-toggle");
            this.autoscrollToggle.val(AUTOSCROLL_MODE.ENABLED);
        
            this.controls = $("#indiego-chat .chat-controls");
            
            this.arrangeMenus();
            
            $(window).on('resize', function()
            {
                toolbar.arrangeMenus();
            });
            
            this.state = -1;
            this.setState(TOOLBAR_STATE.INITIAL);
            
            this.sender.on('click', function(event)
            {
                if(event.button != 0)
                    return;
                
                toolbar.send();
            });
            
            this.cancel.on('click', function(event)
            {
                if(event.button != 0)
                    return;
                
                toolbar.setState(TOOLBAR_STATE.INITIAL);
            });
            
            this.smileInserters.on('click', function(event)
            {
                if(event.button != 0)
                    return;
                
                var
                    str = toolbar.input.val(),
                    code = $(this).attr('alt');
                
                str += '[smile]' + code + '[/smile]';
                toolbar.input.val(str);
                toolbar.input.focus();
            });
            
            this.bbInserters.on('click', function(event)
            {
                if(event.button != 0)
                    return;
                
                var
                    type = $(this).attr('alt');
                        
                switch(type)
                {
                    case 'b':
                    case 'i':
                    case 'u':
                    case 's':
                        if(!replaceSelected(toolbar.input, function(text)
                            {
                                return '[' + type + ']' + text + '[/' + type + ']';
                            }))
                        {
                            var
                                pos = toolbar.input.val().length;
                                
                            toolbar.input.val(toolbar.input.val() + '[' + type + '][/' + type + ']');
                            moveCaret(toolbar.input, pos + 3);
                        }
                        
                        break;
                    case 'url':
                        if(!replaceSelected(toolbar.input, function(text)
                            {
                                return '[url=""]' + text + '[/url]';
                            }))
                            toolbar.input.val(toolbar.input.val() + '[url=""][/url]');
                        break;
                }
            });
            
            this.colorInserters.on('click', function(event)
            {
                if(event.button != 0)
                    return;
                
                var
                    color = $(this).css('background-color');
                        
                if(!replaceSelected(toolbar.input, function(text)
                    {
                        return '[color="' + color + '"]' + text + '[/color]';
                    }))
                {
                    var
                        pos = toolbar.input.val().length;
                        
                    toolbar.input.val(toolbar.input.val() + '[color="' + color + '"][/color]');
                    moveCaret(toolbar.input, pos + color.length + 10);
                }
            });
            
            this.soundToggle.on('click', function(event)
            {
                if(event.button != 0)
                    return;
                
                chat.soundEnabled = !chat.soundEnabled;
                $(this).val(chat.soundEnabled ? SOUND_MODE.ENABLED : SOUND_MODE.DISABLED);
            });
            
            this.autoscrollToggle.on('click', function(event)
            {
                if(event.button != 0)
                    return;
                
                chat.autoscrollEnabled = !chat.autoscrollEnabled;
                $(this).val(chat.autoscrollEnabled ? AUTOSCROLL_MODE.ENABLED : AUTOSCROLL_MODE.DISABLED);
            });
            
            this.input.on('keydown', function(event)
            {
                switch(event.which)
                {
                    case 13: // enter
                        toolbar.send();
                        break;
                    
                    case 38: // up
                        chat.undo();
                        break;
                    
                    case 40: // down
                        chat.redo();
                        break;
                }  
            });
            
            this.input.on('click', function(event)
            {
                event.preventDefault();
                event.stopPropagation();
                
                return false;
            });
            
            this.input.on('blur', function(event)
            {
                if(toolbar.menus.is(event.relatedTarget) || toolbar.menus.children().is(event.relatedTarget))
                    toolbar.input.focus();
            });
            
            this.input.on('focusout', function(event)
            {
                if(toolbar.menus.is(event.relatedTarget) || toolbar.menus.children().is(event.relatedTarget))
                {
                    toolbar.input.focusin();
                
                    event.preventDefault();
                    return false;
                }
                
                return true;
            });
            
            if(this.input.length > 0)
            {
                this.input[0].addEventListener('drop', function(event)
                {
                    if(!event.dataTransfer)
                        return;
                    
                    var
                        link = event.dataTransfer.getData('URL'),
                        text = null;
                        
                    try
                    {
                        text = event.dataTransfer.getData('text/html');
                    }
                    catch(e)
                    {
                        text = event.dataTransfer.getData('text');
                    }
                    
                    if(!link)
                        return;
                    
                    event.stopPropagation();
                    event.preventDefault();
                    
                    if(text.match(/^(?:http|https|ftp).*/))
                    {
                        toolbar.appendInput('[url="' + link + '"]' + link + '[/url]');
                        return;
                    }
                    
                    var
                        linkElement = $(text);
                        
                    if(linkElement.is("a"))
                    {
                        toolbar.appendInput('[url="' + link + '"]' + (linkElement.html() || link) + '[/url]');
                        return;
                    }
                    
                    if(linkElement.is("img"))
                    {
                        if(linkElement.hasClass("smile-inserter"))
                            toolbar.appendInput('[smile]' + linkElement.attr('alt') + '[/smile]');
                        else
                            toolbar.appendInput('[url="' + link + '"]' + (linkElement.attr('alt') || link) + '[/url]');
                            
                        return;
                    }
                    
                    toolbar.appendInput('[url="' + link + '"]' + link + '[/url]');
                    
                }, false);
            }
        }
        
        ChatToolbar.prototype =
        {
            arrangeMenus : function()
            {
                if(this.currentMenu == null)
                    return;
                
                var
                    controlsWidth = this.controls.outerWidth(),
                    controlsOffset = this.controls.offset(),
                    panel = this.currentMenu.children(".panel"),
                    padding = parseFloat(panel.css('padding-left')) + parseFloat(panel.css('padding-right')),
                    border = parseFloat(panel.css('border-left-width')) + parseFloat(panel.css('border-right-width'))
                    min_width = controlsWidth - (padding + border) + 8;
             
                panel.css('min-width', min_width);
                
                if(panel.is('#indiego-chat-smiles > *'))
                    panel.css('max-width', min_width);
                
                if(panel.is(this.settingsPanel))
                    panel.children().width(panel.width() - 20);
                
                panel.offset({left: controlsOffset.left + controlsWidth - panel.outerWidth(false) + 4});
                panel.css('margin-top', -panel.outerHeight(false));
            },
            
            send: function()
            {
                this.senderCallback();
                this.setState(TOOLBAR_STATE.INITIAL);
            },
            
            setState: function(state)
            {
                if(this.state == state)
                    return;
                
                this.state = state;
                
                switch(this.state)
                {
                    case TOOLBAR_STATE.INITIAL:
                        this.input.val('');
                        this.sender.val(this.senderCaption);
                        this.senderCallback = function()
                        {
                            chat.sendMessage(chat.flushInput());
                        };
                        
                        this.cancel.hide();
                        break;
                    
                    case TOOLBAR_STATE.EDIT:
                        this.input.val(chat.getSelectedMessageText());
                        this.sender.val('Заменить');
                        this.senderCallback = function()
                        {
                            chat.editMessage(chat.flushInput());
                        };
                        
                        this.cancel.show();
                        break;
                }
            },
            
            appendInput: function(text)
            {
                this.input.val(this.input.val() + text);
            },
            
            switchCurrentMenu: function(menu)
            {
                if(this.currentMenu != null)
                    this.currentMenu.removeClass("focus");
                
                this.currentMenu = menu;
                
                if(this.currentMenu != null)
                {
                    this.currentMenu.addClass("focus");
                    this.arrangeMenus();
                }
            }
        };
//  }

    w.load(function()
    {
        new Chat();
        new ChatToolbar();
    });
//})(jQuery);