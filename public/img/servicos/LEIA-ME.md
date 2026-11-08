# Imagens dos carrosséis 3D

Cada serviço lê as imagens da pasta com o **nome do serviço em minúsculas e com hífenes**:

| Serviço          | Pasta                              |
|------------------|------------------------------------|
| Haircut          | `public/img/servicos/haircut/`     |
| Beard            | `public/img/servicos/beard/`       |
| Hair and Beard   | `public/img/servicos/hair-and-beard/` |
| Fade             | `public/img/servicos/fade/`        |
| Coloring         | `public/img/servicos/coloring/`    |

- Formatos: jpg, jpeg, png, webp, avif, gif.
- A ordem é a do nome do ficheiro (`1.jpg`, `2.jpg`, `3.jpg`...).
- Proporção ideal: 4:5 (vertical), cerca de 600 x 750 px, até 80 KB cada.
- Um serviço novo usa a pasta com o seu nome em formato slug (ex.: "Corte Criança" -> `corte-crianca`).
- Sem imagens: em ambiente local aparecem espaços reservados; em produção o carrossel não aparece.
