<?php

namespace Rinsvent\Data2DTO\Transformer;

#[\Attribute]
class Trim extends Meta
{
   public string $characters = " \t\n\r\0\x0B";
}