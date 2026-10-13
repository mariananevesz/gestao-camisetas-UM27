<?php

class PdfService
{
    private $paginas = [];
    private $paginaAtual = -1;
    private $y = 0;
    private $largura = 595.28;
    private $altura = 841.89;
    private $margem = 42;

    public function __construct()
    {
        $this->novaPagina();
    }

    public function novaPagina()
    {
        $this->paginas[] = '';
        $this->paginaAtual = count($this->paginas) - 1;
        $this->y = $this->altura - $this->margem;
    }

    public function titulo($texto, $subtitulo = '')
    {
        $this->garantirEspaco(74);
        $this->retangulo($this->margem, $this->y - 48, $this->largura - ($this->margem * 2), 54, [37, 99, 235]);
        $this->desenharTexto($texto, $this->margem + 15, $this->y - 17, 17, true, [255, 255, 255]);
        if ($subtitulo !== '') {
            $this->desenharTexto($subtitulo, $this->margem + 15, $this->y - 35, 8.5, false, [219, 234, 254]);
        }
        $this->y -= 70;
    }

    public function secao($texto)
    {
        $this->garantirEspaco(34);
        $this->y -= 4;
        $this->desenharTexto($texto, $this->margem, $this->y, 12, true, [17, 24, 39]);
        $this->y -= 9;
        $this->linha([209, 213, 219]);
        $this->y -= 14;
    }

    public function texto($texto, $tamanho = 9.5, $negrito = false, $cor = [55, 65, 81], $recuo = 0)
    {
        $larguraUtil = $this->largura - ($this->margem * 2) - $recuo;
        $linhas = $this->quebrarTexto((string) $texto, $larguraUtil, $tamanho);
        $entrelinha = $tamanho + 4;
        $this->garantirEspaco(count($linhas) * $entrelinha + 2);

        foreach ($linhas as $linha) {
            $this->desenharTexto($linha, $this->margem + $recuo, $this->y, $tamanho, $negrito, $cor);
            $this->y -= $entrelinha;
        }
    }

    public function chaveValor($chave, $valor)
    {
        $this->garantirEspaco(17);
        $this->desenharTexto($chave . ':', $this->margem, $this->y, 9, true, [75, 85, 99]);
        $deslocamento = min(155, max(82, $this->comprimento($chave) * 5.3 + 12));
        $this->desenharTexto((string) $valor, $this->margem + $deslocamento, $this->y, 9, false, [17, 24, 39]);
        $this->y -= 16;
    }

    public function espaco($altura = 10)
    {
        $this->y -= $altura;
    }

    public function linha($cor = [209, 213, 219])
    {
        $this->garantirEspaco(5);
        [$r, $g, $b] = $this->corPdf($cor);
        $this->adicionar(sprintf(
            "%.3F %.3F %.3F RG 0.7 w %.2F %.2F m %.2F %.2F l S\n",
            $r,
            $g,
            $b,
            $this->margem,
            $this->y,
            $this->largura - $this->margem,
            $this->y
        ));
    }

    public function barra($rotulo, $valor, $maximo, $cor = [37, 99, 235], $complemento = '')
    {
        $this->garantirEspaco(34);
        $valor = (float) $valor;
        $maximo = max(1, (float) $maximo);
        $larguraBarra = ($this->largura - ($this->margem * 2)) * min(1, $valor / $maximo);
        $textoValor = rtrim(rtrim(number_format($valor, 2, ',', '.'), '0'), ',');
        if ($complemento !== '') {
            $textoValor .= ' ' . $complemento;
        }

        $this->desenharTexto($rotulo, $this->margem, $this->y, 8.5, true, [55, 65, 81]);
        $this->desenharTexto($textoValor, $this->largura - $this->margem - 62, $this->y, 8.5, true, [75, 85, 99]);
        $this->y -= 15;
        $this->retangulo($this->margem, $this->y, $this->largura - ($this->margem * 2), 7, [229, 231, 235]);
        if ($larguraBarra > 0) {
            $this->retangulo($this->margem, $this->y, $larguraBarra, 7, $cor);
        }
        $this->y -= 16;
    }

    public function novaPaginaSeNecessario($altura)
    {
        $this->garantirEspaco($altura);
    }

    public function saida($nomeArquivo)
    {
        foreach ($this->paginas as $indice => &$conteudo) {
            $numero = $indice + 1;
            $total = count($this->paginas);
            $rodape = 'UMADEPRESP 2027  •  Página ' . $numero . ' de ' . $total;
            $conteudo .= $this->comandoTexto($rodape, $this->margem, 24, 7.5, false, [107, 114, 128]);
        }
        unset($conteudo);

        $pdf = $this->montarPdf();
        header('Content-Type: application/pdf');
        header('Content-Disposition: inline; filename="' . preg_replace('/[^A-Za-z0-9._-]/', '-', $nomeArquivo) . '"');
        header('Content-Length: ' . strlen($pdf));
        echo $pdf;
        exit;
    }

    private function garantirEspaco($alturaNecessaria)
    {
        if ($this->y - $alturaNecessaria < 46) {
            $this->novaPagina();
        }
    }

    private function desenharTexto($texto, $x, $y, $tamanho, $negrito, array $cor)
    {
        $this->adicionar($this->comandoTexto($texto, $x, $y, $tamanho, $negrito, $cor));
    }

    private function comandoTexto($texto, $x, $y, $tamanho, $negrito, array $cor)
    {
        [$r, $g, $b] = $this->corPdf($cor);
        $fonte = $negrito ? 'F2' : 'F1';
        $texto = $this->escaparTexto($texto);

        return sprintf(
            "BT /%s %.2F Tf %.3F %.3F %.3F rg 1 0 0 1 %.2F %.2F Tm (%s) Tj ET\n",
            $fonte,
            $tamanho,
            $r,
            $g,
            $b,
            $x,
            $y,
            $texto
        );
    }

    private function retangulo($x, $y, $largura, $altura, array $cor)
    {
        [$r, $g, $b] = $this->corPdf($cor);
        $this->adicionar(sprintf(
            "%.3F %.3F %.3F rg %.2F %.2F %.2F %.2F re f\n",
            $r,
            $g,
            $b,
            $x,
            $y,
            max(0, $largura),
            $altura
        ));
    }

    private function corPdf(array $cor)
    {
        return [$cor[0] / 255, $cor[1] / 255, $cor[2] / 255];
    }

    private function adicionar($comando)
    {
        $this->paginas[$this->paginaAtual] .= $comando;
    }

    private function quebrarTexto($texto, $largura, $tamanho)
    {
        $maximoCaracteres = max(15, (int) floor($largura / ($tamanho * .52)));
        $palavras = preg_split('/\s+/u', trim($texto));
        $linhas = [];
        $linha = '';

        foreach ($palavras as $palavra) {
            $candidata = $linha === '' ? $palavra : $linha . ' ' . $palavra;
            if ($this->comprimento($candidata) <= $maximoCaracteres) {
                $linha = $candidata;
            } else {
                if ($linha !== '') {
                    $linhas[] = $linha;
                }
                $linha = $palavra;
            }
        }

        if ($linha !== '') {
            $linhas[] = $linha;
        }

        return $linhas ?: [''];
    }

    private function comprimento($texto)
    {
        return function_exists('mb_strlen') ? mb_strlen($texto, 'UTF-8') : strlen($texto);
    }

    private function escaparTexto($texto)
    {
        $convertido = function_exists('iconv')
            ? iconv('UTF-8', 'Windows-1252//TRANSLIT//IGNORE', (string) $texto)
            : (string) $texto;
        $convertido = $convertido === false ? (string) $texto : $convertido;
        return str_replace(['\\', '(', ')', "\r", "\n"], ['\\\\', '\\(', '\\)', ' ', ' '], $convertido);
    }

    private function montarPdf()
    {
        $objetos = [];
        $objetos[1] = '<< /Type /Catalog /Pages 2 0 R >>';
        $objetos[3] = '<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica /Encoding /WinAnsiEncoding >>';
        $objetos[4] = '<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica-Bold /Encoding /WinAnsiEncoding >>';

        $idsPaginas = [];
        foreach ($this->paginas as $indice => $conteudo) {
            $paginaId = 5 + ($indice * 2);
            $conteudoId = $paginaId + 1;
            $idsPaginas[] = $paginaId . ' 0 R';

            $objetos[$paginaId] = sprintf(
                '<< /Type /Page /Parent 2 0 R /MediaBox [0 0 %.2F %.2F] /Resources << /Font << /F1 3 0 R /F2 4 0 R >> >> /Contents %d 0 R >>',
                $this->largura,
                $this->altura,
                $conteudoId
            );
            $objetos[$conteudoId] = "<< /Length " . strlen($conteudo) . ">>\nstream\n" . $conteudo . "endstream";
        }

        $objetos[2] = '<< /Type /Pages /Kids [' . implode(' ', $idsPaginas) . '] /Count ' . count($idsPaginas) . ' >>';
        ksort($objetos);

        $pdf = "%PDF-1.4\n%\xE2\xE3\xCF\xD3\n";
        $offsets = [0];
        $totalObjetos = max(array_keys($objetos));

        for ($id = 1; $id <= $totalObjetos; $id++) {
            $offsets[$id] = strlen($pdf);
            $pdf .= $id . " 0 obj\n" . $objetos[$id] . "\nendobj\n";
        }

        $inicioXref = strlen($pdf);
        $pdf .= "xref\n0 " . ($totalObjetos + 1) . "\n";
        $pdf .= "0000000000 65535 f \n";
        for ($id = 1; $id <= $totalObjetos; $id++) {
            $pdf .= sprintf("%010d 00000 n \n", $offsets[$id]);
        }

        $pdf .= "trailer\n<< /Size " . ($totalObjetos + 1) . " /Root 1 0 R >>\n";
        $pdf .= "startxref\n" . $inicioXref . "\n%%EOF";
        return $pdf;
    }
}
