<?php
/**
 *  @package    IndieGo Chat Adaptation for FluxBB
 *  @file       view.php - Внедряет html-код чата
 *  
 *  @version    1.0
 *  @date       2015-06-08
 *  @author     IndieGo (indiego.mttt@gmail.com)
 *  @sponsor    Volkula (volkula@gmail.com)
 *  
 *  @copyright  Copyright (c) IndieGo, 2015
 */

namespace IndieGoChat
{
    require_once PUN_ROOT . 'chat/config.php';
    require_once PUN_ROOT . 'chat/log.php';
	
    Log::start("chat/view.log");
    
    require_once PUN_ROOT . 'chat/chat.php';
    
    try
    {
		Chat::initialize();
        Command::clearAll(Session::id());
    }
    catch(\Exception $ex)
    {
        Log::write("Exception: " . $ex->getMessage());
		throw new \Exception("Exception in the chat module!", 0, $ex);
    }
?>

<div id="idx0" class="blocktable">
	<h2><span><?php echo Config::TITLE; ?></span></h2>
	<div class="box">
		<div class="inbox">
			<table id="indiego-chat" cellspacing="0">
                <thead>
                    <tr>
                        <th class="tcl" scope="col">
                            <?php echo Chat::htmlInput(); ?>
                        </th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td>
							<?php echo Chat::htmlContents(); ?>
						</td>
                    </tr>
                    <tr>
                        <th scope="col">
                            <?php echo Chat::htmlToolbar(); ?>
                        </th>
                    </tr>
                </tbody>
			</table>
		</div>
	</div>
</div>

<?php
}
?>