# Player Locations

## Room

```xml
<connection connection="cluster_01_connection">
  <component class="cluster" macro="cluster_01_macro" connection="galaxy" code="DDZ-200" knownto="player" known="1" read="0" id="[0x1251]">
    <connections>
      <connection connection="c01s01_region002_connection">
        <component class="region" macro="c01s01_region002_macro" connection="cluster" id="[0x1253]">
            <connection connection="zone004_cluster_01_sector001_connection">
              <component class="zone" macro="zone004_cluster_01_sector001_macro" connection="sector" code="RII-277" knownto="player" id="[0x1313]">
                <connections>
                  <connection connection="stations">
                    <component class="station" macro="station_pla_headquarters_base_01_macro" connection="space" code="PQC-446" owner="player" knownto="player" basename="{20102,2011}" level="0.2" variation="0" spawntime="0" modulevariation="0" id="[0x19759]">
                      <connections>
                        <connection connection="modules">
                          <component class="production" macro="landmarks_player_hq_01_research_macro" connection="space" name="{20104,101701}" construction="[0x142]" operationaltime="0" id="[0x1975b]">
                            <connections>
                              <connection connection="con_room_001">
                                <component class="navcontext" macro="virtual_navcontext_macro" connection="space" name="{20007,1171}" id="[0xa6703]">
                                  <connections>
                                    <connection connection="rooms">
                                      <component class="room" macro="room_gen_boronoffice_01_macro" connection="space" seed="4009652551733623167" id="[0xa6705]">
                                        <connections>
                                          <connection connection="player">
                                            <component class="player" macro="character_player_scientist_macro" connection="room" name="{1021,802}" code="WXT-481" owner="player" known="1" read="0" id="[0x11e1c]">
```