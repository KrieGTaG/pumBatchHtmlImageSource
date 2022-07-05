<?php

function getScreenFormat($dirName) {
    $screenFormat = '
        import React, { useState } from \'react\';
        import { ScrollView, View } from \'react-native\';
        import CustomHtmlRenderer from \'../../components/general/CustomHtmlRenderer\';
        import ModalAddPhoto from \'../../components/photos/ModalAddPhoto\';
        import PagePhotoGallery from \'../../components/photos/PagePhotoGallery\';
        %s
        %s
        
        export default function ArachnidScreen(props: { routeName: string }) {
          const [modalAddPhotoVisible, setModalAddPhotoVisible] = useState(false);
          const [hasImages, setHasImages] = useState(false);
        
          return (
            <View>
              <ScrollView>
                <CustomHtmlRenderer
                  immageList={%s[props.routeName]}
                  source={{ html: %s[props.routeName] }}
                ></CustomHtmlRenderer>
                <PagePhotoGallery
                  routeName={props.routeName}
                  setModalAddPhotoVisible={setModalAddPhotoVisible}
                  setHasImages={setHasImages}
                  hasImages={hasImages}
                  modalAddPhotoVisible={modalAddPhotoVisible}
                ></PagePhotoGallery>
              </ScrollView>
        
              <ModalAddPhoto
                modalPhotoVisible={modalAddPhotoVisible}
                setModalAddPhotoVisible={setModalAddPhotoVisible}
                routeName={props.routeName}
                setHasImages={setHasImages}
              ></ModalAddPhoto>
            </View>
          );
        }';

    return sprintf(
        $screenFormat,
        'import pages' . $dirName . ' from \'static_assets/pages/'. $dirName . '/pages' . $dirName . '\';',
        'import images' . $dirName . ' from \'static_assets/pages/'. $dirName . '/images' . $dirName . '\';',
        'images' . $dirName,
        'pages' . $dirName
    );
}
