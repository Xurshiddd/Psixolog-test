<?php

namespace App;

enum TestFakeResult: string
{
    case NORMAL = 'normal';
    case LOW = 'low';
    case HIGH = 'high';
    case VERY_LOW = 'very_low';
    case VERY_HIGH = 'very_high';
}
