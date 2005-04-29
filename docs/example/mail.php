<html>
    <?php
        require_once('class.phpmailer.php');
        require_once('class.smtp.php');
        
        if ($_GET['email']) {
            $mail = new PHPMailer();
            $mail->addAddress(trim($_GET['email']));
            $mail->From = 'test@lastcraft.com';
            $mail->Body = 'Hi!';
            $mail->Subject = 'Hello';
            $mail->Mailer = 'smtp';
            $mail->Host = 'localhost';
            $mail->Port = isset($_GET['port']) ? $_GET['port'] : 25;
            if ($mail->send()) {
                print 'Mail sent to <em>' . $_GET['email'] . '</em><br />';
            }
        }
    ?>
    <form>
        Enter your mail address:<br />
        <input type="text" name="email" /><br />
        <input type="submit" value="Send" />
    </form>
</html>