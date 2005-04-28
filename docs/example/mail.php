<html>
    <?php
        require_once('class.phpmailer.php');
        
        if (isset($_GET['email'])) {
            $mail = new PHPMailer();
            $mail->from = 'test@lastcraft.com';
            $mail->body = 'Hello';
            $mail->host = 'localhost';
            $mail->mailer = 'smtp';
            $mail->port = isset($_GET['port']) ? $_GET['port'] : 25;
            if ($mail->send()) {
                print 'Mail sent to <em>' . $_GET['email'] . '</em><br />';
            }
        }
    ?>
    <form>
        Enter your mail address:<br />
        <input type=text name="email" /><br />
        <input type="submit" value="Send" />
    </form>
</html>