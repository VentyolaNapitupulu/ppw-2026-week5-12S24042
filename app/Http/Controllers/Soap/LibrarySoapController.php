<?php

namespace App\Http\Controllers\Soap;

use App\Http\Controllers\Controller;
use App\Models\Peminjaman;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class LibrarySoapController extends Controller
{
    private const NS = 'http://sipus.del.ac.id/soap/library';

    /**
     * GET /soap/sipus.wsdl
     * Menyajikan kontrak WSDL untuk operasi CheckClearanceStatus.
     */
    public function wsdl(): Response
    {
        $endpointUrl = url('/soap/sipus');
        $ns = self::NS;

        $wsdlXml = <<<XML
<?xml version="1.0" encoding="UTF-8"?>
<definitions name="SipusLibrarySoapService"
    targetNamespace="{$ns}"
    xmlns:tns="{$ns}"
    xmlns:soap="http://schemas.xmlsoap.org/wsdl/soap/"
    xmlns:xsd="http://www.w3.org/2001/XMLSchema"
    xmlns="http://schemas.xmlsoap.org/wsdl/">

    <!-- 1. Message: parameter input & output -->
    <message name="CheckClearanceStatusRequest">
        <part name="nim_peminjam" type="xsd:string"/>
    </message>
    <message name="CheckClearanceStatusResponse">
        <part name="nim_peminjam" type="xsd:string"/>
        <part name="nama_peminjam" type="xsd:string"/>
        <part name="active_loans" type="xsd:int"/>
        <part name="is_cleared" type="xsd:boolean"/>
    </message>

    <!-- 2. PortType: daftar operasi -->
    <portType name="SipusLibraryPortType">
        <operation name="CheckClearanceStatus">
            <documentation>Cek status bebas pustaka mahasiswa untuk keperluan yudisium.</documentation>
            <input message="tns:CheckClearanceStatusRequest"/>
            <output message="tns:CheckClearanceStatusResponse"/>
        </operation>
    </portType>

    <!-- 3. Binding: operasi diikat ke SOAP over HTTP -->
    <binding name="SipusLibrarySoapBinding" type="tns:SipusLibraryPortType">
        <soap:binding style="rpc" transport="http://schemas.xmlsoap.org/soap/http"/>
        <operation name="CheckClearanceStatus">
            <soap:operation soapAction="{$ns}#CheckClearanceStatus"/>
            <input><soap:body use="literal" namespace="{$ns}"/></input>
            <output><soap:body use="literal" namespace="{$ns}"/></output>
        </operation>
    </binding>

    <!-- 4. Service: alamat endpoint fisik -->
    <service name="SipusLibrarySoapService">
        <port name="SipusLibraryPort" binding="tns:SipusLibrarySoapBinding">
            <soap:address location="{$endpointUrl}"/>
        </port>
    </service>
</definitions>
XML;

        return response($wsdlXml, 200)
            ->header('Content-Type', 'text/xml; charset=utf-8');
    }

    /**
     * POST /soap/sipus
     * Memproses <soap:Envelope> berisi <nim_peminjam>.
     */
    public function handle(Request $request): Response
    {
        $rawXml = trim($request->getContent());

        if ($rawXml === '') {
            return $this->soapFault('soap:Client', 'Body XML SOAP tidak boleh kosong.');
        }

        // LIBXML_NONET: larang pemuatan sumber eksternal (mitigasi XXE)
        libxml_use_internal_errors(true);
        $xml = simplexml_load_string($rawXml, 'SimpleXMLElement', LIBXML_NONET);
        libxml_clear_errors();

        if ($xml === false) {
            return $this->soapFault('soap:Client', 'Format XML tidak valid (Malformed XML).');
        }

        // Pastikan akar dokumen adalah <Envelope> dan memiliki <Body>
        if ($xml->getName() !== 'Envelope'
            || empty($xml->xpath('/*[local-name()="Envelope"]/*[local-name()="Body"]'))) {
            return $this->soapFault('soap:Client', 'Dokumen bukan SOAP Envelope yang valid (elemen <Envelope>/<Body> tidak ditemukan).');
        }

        $operasi = $xml->xpath('//*[local-name()="Body"]/*[local-name()="CheckClearanceStatusRequest"]');

        if (empty($operasi)) {
            return $this->soapFault('soap:Client', 'Operasi tidak dikenali. Gunakan CheckClearanceStatusRequest.');
        }

        $nimNodes = $operasi[0]->xpath('./*[local-name()="nim_peminjam"]');
        $nim = empty($nimNodes) ? '' : strtoupper(trim((string) $nimNodes[0]));

        if ($nim === '') {
            return $this->soapFault('soap:Client', 'Elemen <nim_peminjam> wajib diisi dan tidak boleh kosong.');
        }

        // Data nyata dari tabel peminjamans (Minggu 6)
        $activeLoans = Peminjaman::where('nim_peminjam', $nim)
            ->where('status', 'dipinjam')
            ->count();

        $namaPeminjam = Peminjaman::where('nim_peminjam', $nim)
            ->latest('id')
            ->value('nama_peminjam') ?? '';

        $isCleared = $activeLoans === 0 ? 'true' : 'false';

        $safeNim  = htmlspecialchars($nim, ENT_XML1, 'UTF-8');
        $safeNama = htmlspecialchars($namaPeminjam, ENT_XML1, 'UTF-8');
        $ns = self::NS;

        $responseXml = <<<XML
<?xml version="1.0" encoding="UTF-8"?>
<soap:Envelope xmlns:soap="http://schemas.xmlsoap.org/soap/envelope/"
               xmlns:m="{$ns}">
    <soap:Header>
        <m:ProviderInfo>SIPUS-Del Legacy SOAP Engine v1.0</m:ProviderInfo>
    </soap:Header>
    <soap:Body>
        <m:CheckClearanceStatusResponse>
            <m:nim_peminjam>{$safeNim}</m:nim_peminjam>
            <m:nama_peminjam>{$safeNama}</m:nama_peminjam>
            <m:active_loans>{$activeLoans}</m:active_loans>
            <m:is_cleared>{$isCleared}</m:is_cleared>
        </m:CheckClearanceStatusResponse>
    </soap:Body>
</soap:Envelope>
XML;

        return response($responseXml, 200)
            ->header('Content-Type', 'text/xml; charset=utf-8');
    }

    /**
     * Pembentuk pesan galat standar SOAP 1.1 (<soap:Fault>).
     * Sesuai spesifikasi SOAP 1.1, Fault dikirim dengan HTTP 500.
     */
    private function soapFault(string $faultCode, string $faultMessage): Response
    {
        $safeMsg = htmlspecialchars($faultMessage, ENT_XML1, 'UTF-8');

        $faultXml = <<<XML
<?xml version="1.0" encoding="UTF-8"?>
<soap:Envelope xmlns:soap="http://schemas.xmlsoap.org/soap/envelope/">
    <soap:Body>
        <soap:Fault>
            <faultcode>{$faultCode}</faultcode>
            <faultstring>{$safeMsg}</faultstring>
        </soap:Fault>
    </soap:Body>
</soap:Envelope>
XML;

        return response($faultXml, 500)
            ->header('Content-Type', 'text/xml; charset=utf-8');
    }
}