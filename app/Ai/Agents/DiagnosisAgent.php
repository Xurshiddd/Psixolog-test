<?php

namespace App\Ai\Agents;

use Laravel\Ai\Contracts\Agent;
use Laravel\Ai\Contracts\Conversational;
use Laravel\Ai\Contracts\HasTools;
use Laravel\Ai\Contracts\Tool;
use Laravel\Ai\Messages\Message;
use Laravel\Ai\Promptable;
use Stringable;

class DiagnosisAgent implements Agent, Conversational, HasTools
{
    use Promptable;

    /**
     * Get the instructions that the agent should follow.
     */
    public function instructions(): Stringable|string
    {
        return <<<INSTRUCTIONS
        Siz malakali psixolog yordamchisisiz.
        Sizga modul nomi, modul tavsifi, test savollari, tanlangan javoblar va tanlanmagan javoblar beriladi.
        Siz faqat shu ma'lumotlarga tayangan holda psixolog uchun qoralama xulosa yozishingiz kerak.

        Qoidalar:
        1. Xulosani o'zbek tilida yozing.
        2. Natijani aniq 2 bo'limda yozing:
        E'tibor talab qiladigan jihatlar:
        Tavsiyalar:
        3. Har bir bo'lim 1-3 jumladan oshmasin.
        4. Modul nomi va modul tavsifini e'tiborga oling.
        5. Har bir xulosani test savollari va tanlangan/tanlanmagan variantlarga tayangan holda yozing.
        6. Medikal tashxis qo'ymang, keskin hukm chiqarmang, noma'lum narsani o'ylab topmang.
        7. Professional, aniq va amaliy ohangda yozing.
        8. Natija psixolog ko'rib chiqishi uchun qoralama xulosa ekanini unutmang.
        9. Agar ma'lumot yetarli bo'lmasa, buni ehtiyotkor tarzda ayting.
        INSTRUCTIONS;
    }

    /**
     * Get the list of messages comprising the conversation so far.
     *
     * @return Message[]
     */
    public function messages(): iterable
    {
        return [];
    }

    /**
     * Get the tools available to the agent.
     *
     * @return Tool[]
     */
    public function tools(): iterable
    {
        return [];
    }
}
