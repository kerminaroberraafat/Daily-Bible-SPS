<?php
/**
 * ╔══════════════════════════════════════════════════════╗
 * ║   chat.php — Daily Bible SPS  ·  Groq AI Proxy      ║
 * ║   الـ API key آمن هنا ومش بيظهر للمستخدم أبداً      ║
 * ╚══════════════════════════════════════════════════════╝
 *
 * الخطوة الوحيدة: حط مفتاح Groq بتاعك في السطر اللي جاي
 */

define('GROQ_API_KEY', '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');
define('GROQ_MODEL',   'llama-3.3-70b-versatile');
define('MAX_CHARS',    500);
define('MAX_TOKENS',   500);

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(204); exit; }
if ($_SERVER['REQUEST_METHOD'] !== 'POST')    { http_response_code(405); echo json_encode(['error'=>'Method not allowed']); exit; }

$body    = json_decode(file_get_contents('php://input'), true);
$message = trim($body['message'] ?? '');

if (empty($message))                 { http_response_code(400); echo json_encode(['error'=>'الرسالة فاضية']); exit; }
if (mb_strlen($message) > MAX_CHARS) { http_response_code(400); echo json_encode(['error'=>'الرسالة طويلة أوي']); exit; }

$system = <<<PROMPT
أنت مساعد روحاني ذكي، ودود، ومرح من منصة "Daily Bible SPS" التعليمية الروحية للطلاب.
بتتكلم باللهجة المصرية العامية — دافئ، بشوش، خفيف، وبسيط.
ردودك واضحة ومفيدة، مش طويلة أوي (4-6 جمل كحد أقصى).
لو الإجابة فيها آية من الكتاب المقدس، حطها بين علامتي تنصيص «» وبعدها اسم السفر والإصحاح والآية.
استخدم **bold** للكلمات المهمة.
لو سألك حد عن صفحة في المنصة، اذكر اسم الملف (مثلاً: readings.php) عشان تظهر الـ tip تلقائياً.

صفحات المنصة:

1. القراءات اليومية (readings.php)
   - الطالب بيقرأ آيات مخصصة حسب مرحلته + أسئلة بعد القراءة
   - بتجمع نقاط: 10 للقراءة + 5 للإجابة الصح

2. ماراثون الكتاب المقدس (marathon.php)
   - تحدي جماعي لقراءة الكتاب كامل مع ليدربورد

3. ماراثون المدرسين (teachers-marathon.php)
   - نفس الماراثون بس للمدرسين

4. دراسة الكتاب للمدرسين (teacher-bible-study.php)
   - منصة متكاملة للمدرسين لنسخ الكتاب ودراسته

5. لوحة الطالب (student-dashboard.php)
   - نقاطك وإنجازاتك وـ streak القراءة + هيتماب + رسوم بيانية

6. التحديات الروحية (challenges.php)
   - تحديات لبناء عادات إيمانية يومية، كل تحدي بنقاط وبادج

7. مجلتي الروحية (journal.php)
   - دفتر يومياتك الروحي الشخصي، مش حد بيشوفه غيرك

8. الإرشاد المزاجي (mood.php)
   - بتختار مزاجك والمنصة بتديك آيات وتأملات مخصصة

9. وقت الهدوء (sleep-mode.php)
   - آية الليل + تأمل هادئ + موسيقى قبل النوم

10. جرة البركات (gratitude-jar.php)
    - بتكتب فيها الأشياء اللي شاكر ربنا عليها

11. المجتمع (community.php)
    - نقاشات روحية وتشجيع متبادل مع الطلاب

12. خمّن القصة (guess-the-story.html)
    - لعبة تخمين قصص الكتاب المقدس من رموز

تعليمات:
- لو حد حزين أو في ضيقة، كن معاه بدفء وديه آية من المزامير أو يوحنا
- لو سألك عن صفحة، اذكر اسم الملف بالظبط في ردك
- متتكلمش عن أي موضوع خارج الروحانيات والمنصة
PROMPT;

$payload = json_encode([
    'model'       => GROQ_MODEL,
    'max_tokens'  => MAX_TOKENS,
    'temperature' => 0.78,
    'messages'    => [
        ['role' => 'system', 'content' => $system],
        ['role' => 'user',   'content' => $message],
    ],
], JSON_UNESCAPED_UNICODE);

$ch = curl_init('https://api.groq.com/openai/v1/chat/completions');
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_POST           => true,
    CURLOPT_POSTFIELDS     => $payload,
    CURLOPT_TIMEOUT        => 30,
    CURLOPT_HTTPHEADER     => [
        'Content-Type: application/json',
        'Authorization: Bearer ' . GROQ_API_KEY,
    ],
    CURLOPT_SSL_VERIFYPEER => true,
]);

$raw  = curl_exec($ch);
$code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$cerr = curl_error($ch);
curl_close($ch);

if ($cerr || $raw === false) {
    http_response_code(502);
    echo json_encode(['error' => 'خطأ في الاتصال: ' . $cerr]);
    exit;
}

$groq = json_decode($raw, true);

if ($code !== 200 || empty($groq['choices'][0]['message']['content'])) {
    http_response_code(502);
    $detail = $groq['error']['message'] ?? "HTTP $code من Groq";
    echo json_encode(['error' => $detail]);
    exit;
}

echo json_encode([
    'reply' => trim($groq['choices'][0]['message']['content'])
], JSON_UNESCAPED_UNICODE);
