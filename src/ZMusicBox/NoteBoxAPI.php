<?php

namespace ZMusicBox;

class NoteBoxAPI {

    const INSTRUMENT_PIANO = 0;
    const INSTRUMENT_BASS_DRUM = 1;
    const INSTRUMENT_CLICK = 2;
    const INSTRUMENT_TABOUR = 3;
    const INSTRUMENT_BASS = 4;

    public $plugin;
    public $length;
    public $sounds = [];
    public $tick;
    public $buffer;
    public $offset = 0;
    public $name;
    public $speed;

    public function __construct($plugin, $path) {
        $this->plugin = $plugin;
        $fopen = fopen($path, "r");
        $this->buffer = fread($fopen, filesize($path));
        fclose($fopen);
        $this->length = $this->getShort();
        $height = $this->getShort();
        $this->name = $this->getString();
        $this->getString();
        $this->getString();
        $this->getString();
        $this->speed = $this->getShort();
        $this->getByte();
        $this->getByte();
        $this->getByte();
        $this->getInt();
        $this->getInt();
        $this->getInt();
        $this->getInt();
        $this->getInt();
        $this->getString();
        $this->tick = $this->getShort() - 1;
        while (true) {
            $sounds = [];
            $this->getShort();
            while (true) {
                switch ($this->getByte()) {
                    case 1: // BASS
                        $type = self::INSTRUMENT_BASS;
                        break;
                    case 2: // BASS_DRUM
                        $type = self::INSTRUMENT_BASS_DRUM;
                        break;
                    case 3: // CLICK
                        $type = self::INSTRUMENT_CLICK;
                        break;
                    case 4: // TABOUR
                        $type = self::INSTRUMENT_TABOUR;
                        break;
                    default: // PIANO
                        $type = self::INSTRUMENT_PIANO;
                        break;
                }
                if ($height == 0) {
                    $pitch = $this->getByte() - 33;
                } elseif ($height < 10) {
                    $pitch = $this->getByte() - 33 + $height;
                } else {
                    $pitch = $this->getByte() - 48 + $height;
                }
                $sounds[] = [$pitch, $type];
                if ($this->getShort() == 0) {
                    break;
                }
            }
            $this->sounds[$this->tick] = $sounds;
            if (($jump = $this->getShort()) !== 0) {
                $this->tick += $jump;
            } else {
                break;
            }
        }
    }

    public function get($len) {
        if ($len < 0) {
            $this->offset = strlen($this->buffer) - 1;
            return "";
        } elseif ($len === true) {
            return substr($this->buffer, $this->offset);
        }
        return $len === 1 ? $this->buffer[$this->offset++] : substr($this->buffer, ($this->offset += $len) - $len, $len);
    }

    public function getByte() {
        return ord($this->buffer[$this->offset++]);
    }

    public function getInt() {
        return (PHP_INT_SIZE === 8 ? unpack("N", $this->get(4))[1] << 32 >> 32 : unpack("N", $this->get(4))[1]);
    }

    public function getShort() {
        return unpack("S", $this->get(2))[1];
    }

    public function getString() {
        return $this->get(unpack("I", $this->get(4))[1]);
    }

    public function getTick() {
        return $this->tick;
    }

}
