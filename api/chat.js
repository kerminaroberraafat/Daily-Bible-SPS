// api/chat.js
export default async function handler(req, res) {
  // Only allow POST
  if (req.method !== 'POST') {
    return res.status(405).json({ error: 'Method not allowed' });
  }

  const { message } = req.body;

  // Input validation
  const MAX_CHARS = 500;
  if (!message || typeof message !== 'string' || message.trim().length === 0) {
    return res.status(400).json({ error: 'الرسالة فاضية' });
  }
  if (message.length > MAX_CHARS) {
    return res.status(400).json({ error: 'الرسالة طويلة أوي' });
  }

  // Groq API configuration
  const GROQ_API_KEY = process.env.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;  // set in Vercel dashboard
  const GROQ_MODEL   = 'llama-3.3-70b-versatile';
  const MAX_TOKENS   = 500;

  if (!GROQ_API_KEY) {
    console.error('Missing GROQ_API_KEY environment variable');
    return res.status(500).json({ error: 'تكوين الخادم غير مكتمل' });
  }

  // System prompt (same as PHP version)
  const systemPrompt = `أنت مساعد روحاني ذكي، ودود، ومرح من منصة "Daily Bible SPS" التعليمية الروحية للطلاب.
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
- متتكلمش عن أي موضوع خارج الروحانيات والمنصة`;

  try {
    // Call Groq API
    const response = await fetch('https://api.groq.com/openai/v1/chat/completions', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'Authorization': `Bearer ${GROQ_API_KEY}`,
      },
      body: JSON.stringify({
        model: GROQ_MODEL,
        max_tokens: MAX_TOKENS,
        temperature: 0.78,
        messages: [
          { role: 'system', content: systemPrompt },
          { role: 'user', content: message.trim() },
        ],
      }),
    });

    const data = await response.json();

    if (!response.ok) {
      console.error('Groq API error:', data);
      const errorDetail = data.error?.message || `HTTP ${response.status}`;
      return res.status(502).json({ error: errorDetail });
    }

    const reply = data.choices?.[0]?.message?.content?.trim();
    if (!reply) {
      throw new Error('Empty reply from Groq');
    }

    res.status(200).json({ reply });
  } catch (err) {
    console.error('Chat endpoint error:', err);
    res.status(502).json({ error: 'خطأ في الاتصال بالذكاء الاصطناعي' });
  }
}