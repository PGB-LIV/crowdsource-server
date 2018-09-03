<?php
use pgb_liv\php_ms\Core\Spectra\PrecursorIon;
use pgb_liv\php_ms\Core\Modification;
use pgb_liv\php_ms\Core\Protein;
use pgb_liv\php_ms\Core\Peptide;

class MzIdentMlWriter
{

    const SPECTRUM_IDENTIFICATION_PREFIX = 'SI_';

    const SPECTRUM_IDENTIFICATION_PROTOCOL_PREFIX = 'SIP_';

    const SPECTRUM_IDENTIFICATION_LIST_PREFIX = 'SIL_';

    const SPECTRA_DATA_PREFIX = 'SI_';

    const SEARCH_DATABASE_PREFIX = 'SDB_';

    private $path;

    private $stream;

    private $cvList = array();

    private $softwareList = array();

    private $spectraData = array();

    private $searchData = array();

    private $decoyData = array();

    /**
     * Write to the specified stream
     *
     * @param string $path
     */
    public function __construct($path)
    {
        $this->path = $path;
    }

    public function open()
    {
        $this->stream = new XMLWriter();
        $this->stream->openMemory();
        $this->stream->startDocument('1.0', 'UTF-8');

        $this->writeMzIdentMl();
        $this->writeCvList();
        $this->writeAnalysisSoftwareList();
        $this->writeAnalysisCollection();
        $this->writeAnalysisProtocolCollection();
    }

    /**
     * CV terms must be added prior to open being called
     *
     * @param string $name
     * @param string $version
     * @param string $uri
     * @param string $id
     */
    public function addCv($name, $version, $uri, $id)
    {
        $this->cvList[] = array(
            'fullName' => $name,
            'version' => $version,
            'uri' => $uri,
            'id' => $id
        );
    }

    /**
     * Software ID's must be added prior to open being called
     *
     * @param string $name
     * @param string $id
     */
    public function addSoftware($id, $name)
    {
        $this->softwareList[] = array(
            'name' => $name,
            'id' => $id
        );
    }

    public function addSpectraData($path)
    {
        $this->spectraData[] = $path;
    }

    public function addSearchData($path)
    {
        $this->searchData[] = array('path' => $path, 'isDecoy' => false);
    }

    public function addDecoyData($path)
    {
        $this->searchData[] = array('path' => $path, 'isDecoy' => true);
    }

    /**
     *
     * @param PrecursorIon[] $precursors
     */
    public function addIdentifiedPrecursors(array $precursors)
    {
        $this->writeSequenceCollection($precursors);
    }

    public function close()
    {
        // finalise
    }

    private function writeMzIdentMl()
    {
        $this->stream->startElement('MzIdentML');
        $this->stream->writeAttribute('version', '1.2.0');
    }

    private function writeCvList()
    {
        if (empty($this->cvList)) {
            throw new UnexpectedValueException('MzIdentML requires at least one CV to be used');
        }

        $this->stream->startElement('cvList');

        foreach ($this->cvList as $cv) {
            $this->writeCv($cv);
        }

        $this->stream->endElement();
    }

    private function writeCv($cv)
    {
        $this->stream->startElement('cv');
        foreach ($cv as $key => $value) {
            $this->stream->writeAttribute($key, $value);
        }

        $this->stream->endElement();
    }

    private function writeAnalysisSoftwareList()
    {
        if (empty($this->softwareList)) {
            return;
        }

        $this->stream->startElement('AnalysisSoftwareList');

        foreach ($this->softwareList as $software) {
            $this->writeSoftware($software);
        }

        $this->stream->endElement();
    }

    private function writeAnalysisCollection()
    {
        $this->stream->startElement('AnalysisCollection');

        foreach (array_keys($this->searchData) as $id) {
            $this->writeSpectrumIdentification($id);
        }

        $this->stream->endElement();
    }

    private function writeSpectrumIdentification($id)
    {
        $this->stream->startElement('SpectrumIdentification');

        $this->stream->writeAttribute('id', self::SPECTRUM_IDENTIFICATION_PREFIX . $id);
        
        // We only support one of each these elements
        $this->stream->writeAttribute('spectrumIdentificationProtocol_ref', self::SPECTRUM_IDENTIFICATION_PROTOCOL_PREFIX . '1');
        $this->stream->writeAttribute('spectrumIdentificationList_ref', self::SPECTRUM_IDENTIFICATION_LIST_PREFIX . '1');

        $this->writeInputSpectra(1);

        $this->writeSearchDatabaseRef($id);

        $this->stream->endElement();
    }

    private function writeInputSpectra($id)
    {
        $this->stream->startElement('InputSpectra');

        $this->stream->writeAttribute('spectraData_ref', self::SPECTRA_DATA_PREFIX . $id);

        $this->stream->endElement();
    }

    private function writeSearchDatabaseRef($id)
    {
        $this->stream->startElement('SearchDatabaseRef');

        $this->stream->writeAttribute('searchDatabase_ref', self::SEARCH_DATABASE_PREFIX . $id);

        $this->stream->endElement();
    }
    
    private function writeAnalysisProtocolCollection()
    {
        $this->stream->startElement('AnalysisProtocolCollection');
        
        $this->writeSpectrumIdentificationProtocol();
        
        $this->stream->endElement();
    }
    
    private function writeSpectrumIdentificationProtocol()
    {
        // TODO: SearchType? Pass early on
        // TODO: Mod params? Provide a look up for name to ID
        // TODO: Enzymes
        // TODO: Frag tolerance
        // TODO: Parent tolerance
        // TODO: Threshold - no threshold
    }

    private function writeSoftware(array $software)
    {
        $this->stream->startElement('AnalysisSoftware');
        $this->stream->writeAttribute('id', $software['id']);

        $this->stream->writeSoftwareName($software);

        $this->stream->endElement();
    }

    private function writeSoftwareName(array $software)
    {
        $this->stream->startElement('SoftwareName');

        $this->stream->startElement('userParam');
        $this->stream->writeAttribute('name', $software['name']);
        $this->stream->endElement();

        $this->stream->endElement();
    }

    /**
     *
     * @param PrecursorIon[] $precursors
     */
    private function writeSequenceCollection(array $precursors)
    {
        $this->stream->startElement('SequenceCollection');

        $objectsWritten = array();
        foreach ($precursors as $precursor) {
            foreach ($precursor->getIdentifications() as $ident) {
                $proteins = $ident->getSequence()->getProteins();

                foreach ($proteins as $protein) {
                    if (isset($objectsWritten[$protein->getUniqueIdentifier()])) {
                        continue;
                    }

                    $this->writeDbSequence($protein);
                }
            }
        }

        $objectsWritten = array();
        foreach ($precursors as $precursor) {
            foreach ($precursor->getIdentifications() as $ident) {
                $peptide = $ident->getSequence();

                if (isset($objectsWritten[$this->getId($peptide)])) {
                    continue;
                }

                $this->writePeptide($peptide);
            }
        }
        $objectsWritten = null;

        foreach ($precursors as $precursor) {
            foreach ($precursor->getIdentifications() as $ident) {
                $peptide = $ident->getSequence();

                $proteins = $ident->getSequence()->getProteins();

                foreach ($proteins as $protein) {
                    $this->writePeptideEvidence($peptide, $protein);
                }
            }
        }

        $this->stream->endElement();
    }

    private function writeDbSequence(Protein $protein)
    {
        $this->stream->startElement('DBSequence');

        $this->stream->writeAttribute('accession', $protein->getAccession());
        $this->stream->writeAttribute('id', $protein->getUniqueIdentifier());

        $this->writeSeq($protein->getSequence());

        $this->stream->endElement();
    }

    private function writeSeq($sequence)
    {
        $this->stream->writeElement('Seq', $sequence);
    }

    private function getId(Peptide $peptide)
    {
        // TODO: Factor in Mods to ID
        return $peptide->getSequence();
    }

    private function writePeptide(Peptide $peptide)
    {
        $this->stream->startElement('Peptide');

        $this->stream->writeAttribute('id', $this->getId($peptide));

        $this->writePeptideSequence($peptide->getSequence());

        foreach ($peptide->getModifications() as $modification) {
            $this->writeModification($modification);
        }

        $this->stream->endElement();
    }

    private function writePeptideSequence($sequence)
    {
        $this->stream->writeElement('PeptideSequence', $sequence);
    }

    private function writeModification(Modification $modification)
    {
        $this->stream->startElement('Modification');

        $this->stream->writeAttribute('location', $modification->getLocation());
        $this->stream->writeAttribute('monoisotopicMassDelta', $modification->getMonoisotopicMass());

        $this->stream->startElement('userParam');

        $this->stream->writeAttribute('name', $modification->getName());

        $this->stream->endElement();

        $this->stream->endElement();
    }

    private function writePeptideEvidence(Peptide $peptide, Protein $protein)
    {
        $this->stream->startElement('PeptideEvidence');

        $this->stream->writeAttribute('id', $this->getId($peptide) . '_' . $protein->getUniqueIdentifier());
        $this->stream->writeAttribute('dbSequence_ref', $protein->getUniqueIdentifier());
        $this->stream->writeAttribute('peptide_ref', $this->getId($peptide));

        $this->stream->endElement();
    }
}