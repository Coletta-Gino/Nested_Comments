<?php
  // Database connection details
  $DATABASE_HOST = 'localhost';
  $DATABASE_USER = 'nested_comments';
  $DATABASE_PASS = 'nested_comments';
  $DATABASE_NAME = 'nested_comments';

  try {
    $pdo = new PDO("mysql:host=$DATABASE_HOST;dbname=$DATABASE_NAME;charset=utf8", $DATABASE_USER, $DATABASE_PASS);
  } 
  catch (PDOException $exception) {
    exit('Failed to connect to database!');
  }

  // Function to convert datetime to human-readable format
  function time_elapsed_string($datetime, $full = false) {
    $now = new DateTime;
    $ago = new DateTime($datetime);
    $diff = $now->diff($ago);

    $string = ['y' => 'year', 'm' => 'month', 'd' => 'day', 'h' => 'hour', 'i' => 'minute', 's' => 'second'];
    foreach ($string as $k => &$v) {
      if ($diff->$k) {
        $v = $diff->$k . ' ' . $v . ($diff->$k > 1 ? 's' : '');
      } 
      else {
        unset($string[$k]);
      }
    }
    
    if (!$full) {
      $string = array_slice($string, 0, 1);
    }

    return $string ? implode(', ', $string) . ' ago' : 'just now';
  }

  // Function to recursively display comments
  function show_comments($comments, $parent_id = 0) {
    $html = '';
    foreach ($comments as $comment) {
      if ($comment['parent_id'] == $parent_id) {
        $html .= '
        <div class="comment">
          <div>
            <h3 class="name">' . htmlspecialchars($comment['name'], ENT_QUOTES) . '</h3>
            <span class="date">' . time_elapsed_string($comment['submit_date']) . '</span>
          </div>
          <p>' . nl2br(htmlspecialchars($comment['content'], ENT_QUOTES)) . '</p>
          <a class="reply_comment_btn" href="#" data-comment-id="' . $comment['id'] . '">Reply</a>
          ' . show_write_comment_form($comment['id']) . '
          <div class="replies">' . show_comments($comments, $comment['id']) . '</div>
        </div>';
      }
    }
    return $html;
  }

  // Function to show the write comment form
  function show_write_comment_form($parent_id = 0) {
    return '
    <div class="write_comment" data-comment-id="' . $parent_id . '" style="display: none;">
      <form>
        <input name="parent_id" type="hidden" value="' . $parent_id . '">
        <input name="name" type="text" placeholder="Your Name" required>
        <textarea name="content" placeholder="Write your comment here..." required></textarea>
        <button type="submit">Submit Comment</button>
      </form>
    </div>';
  }

  // Check if a comment is being submitted
  if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['name'], $_POST['content'])) {
    $parent_id = $_POST['parent_id'];

    // If it's a root comment (without parent), depth = 0
    if ($parent_id == 0) {
      $depth = 0;
    } 
    else {
      // Else, we retrieve the depth of the parent and we add +1
      $stmt = $pdo->prepare('SELECT depth FROM comments WHERE id = ?');
      $stmt->execute([$parent_id]);
      $parent = $stmt->fetch(PDO::FETCH_ASSOC);
      $depth = ($parent) ? $parent['depth'] + 1 : 0;
    }

    // Insert the comment with the right depth
    $stmt = $pdo->prepare('INSERT INTO comments (parent_id, name, content, depth, submit_date) VALUES (?, ?, ?, ?, NOW())');
    $stmt->execute([$parent_id, $_POST['name'], $_POST['content'], $depth]);

    exit('Your comment has been submitted!');
  }

  // Get all comments ordered properly
  $stmt = $pdo->query('SELECT * FROM comments ORDER BY parent_id ASC, submit_date ASC');
  $comments = $stmt->fetchAll(PDO::FETCH_ASSOC);

  // Get the total number of comments
  $stmt = $pdo->query('SELECT COUNT(*) AS total_comments FROM comments');
  $comments_info = $stmt->fetch(PDO::FETCH_ASSOC);
?>

<div class="comment_header">
  <span class="total"><?= $comments_info['total_comments'] ?> comments</span>
  <a href="#" class="write_comment_btn" data-comment-id="0">Write Comment</a>
</div>

<?= show_write_comment_form(); ?>

<?= show_comments($comments); ?>