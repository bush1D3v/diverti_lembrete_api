<?php

namespace App\Infrastructure\Reminder\Dto;

use Ramsey\Uuid\Uuid;
use App\Domain\Reminder\Reminder;

class ReminderInitialDTO
{
    public static function getDefaultReminders()
    {
        return [
            1 => new Reminder(
                Uuid::uuid4()->toString(), 
                '🌟✨ Não se esqueça de fazer carinho na Belinha hoje! Ela merece todo o nosso amor e alegria! 🐾❤️😊',
                'happiness',
                (new \DateTime())->modify('+'.rand(1,24).' hours'),
                false
            ),
            2 => new Reminder(
                Uuid::uuid4()->toString(),
                'Liberar minhas lágrimas tristes assim que eu entrar em casa 😢💔.',
                'sad',
                (new \DateTime())->modify('+'.rand(1,24).' hours'),
                false
            ),
            3 => new Reminder(
                Uuid::uuid4()->toString(),
                '🔥💥 Quebrar meu videogame! Eu não aguento mais essa frustração! 💢😠',
                'angry',
                (new \DateTime())->modify('+'.rand(1,24).' hours'),
                false
            ),
            4 => new Reminder(
                Uuid::uuid4()->toString(),
                'Tenho que verificar urgentemente a nota do Enem! 🤯📊 Ansiedade a mil, mas preciso ver! 🔍',
                'anxiety', 
                (new \DateTime())->modify('+'.rand(1,24).' hours'),
                false
            ),
            5 => new Reminder(
                Uuid::uuid4()->toString(),
                'Não posso esquecer: o dia do aniversário da Bruna 😒🎂. Ela sempre se diverte mais nos aniversários...',
                'envy',
                (new \DateTime())->modify('+'.rand(1,24).' hours'),
                false
            ),
            6 => new Reminder(
                Uuid::uuid4()->toString(),
                'Tenho que encarar minha vergonha e enviar aquela mensagem que estou adiando para aquela pessoa. 😰📨',
                'shame',
                (new \DateTime())->modify('+'.rand(1,24).' hours'),
                false
            ),
            7 => new Reminder(
                Uuid::uuid4()->toString(),
                'Encarar a caverna escura e sombria 👻🕯, mesmo com medo! 💪🫣',
                'fear',
                (new \DateTime())->modify('+'.rand(1,24).' hours'),
                false
            ),
            8 => new Reminder(
                Uuid::uuid4()->toString(),
                'Hoje é o dia de desejar parabéns para aquela pessoa irritante 🤢🤮. Mesmo detestando, tenho que cumprir essa obrigação!',
                'disgust',
                (new \DateTime())->modify('+'.rand(1,24).' hours'),
                false
            ),
            9 => new Reminder(
                Uuid::uuid4()->toString(),
                '✨ Noite entediante... Hora de jogar aquele jogo e animar! 🎮✨',
                'boredom',
                (new \DateTime())->modify('+'.rand(1,24).' hours'),
                false
            ),
        ];
    }
}