namespace App\Enums;

enum Category: string
{
    case cabelo = 'cabelo';
    case barba = 'barba';
    case cabelo_barba = 'cabelo e barba';
    case degrada = 'degrada';
    case coloracao = 'coloração';
}