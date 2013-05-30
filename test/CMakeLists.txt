cmake_minimum_required(VERSION 2.8)

project(test)

include_directories("${PROJECT_BINARY_DIR}")

set(CMAKE_MODULE_PATH ${CMAKE_MODULE_PATH} "${PROJECT_SOURCE_DIR}/cmake/modules")

file(GLOB_RECURSE test_HEADERS src/*.hpp)
file(GLOB_RECURSE test_SOURCES src/*.cpp)

add_executable(test
	${test_SOURCES}
)

add_definitions("-Wall -Wextra -Weffc++ -std=c++0x -pedantic -g3 -O3")

set(CMAKE_MODULE_PATH ${CMAKE_MODULE_PATH}
                      "${PROJECT_SOURCE_DIR}/cmake/modules")

find_package(Boost COMPONENTS system program_options iostreams REQUIRED)

include_directories(SYSTEM
                    ${Boost_INCLUDE_DIRS})
                    
target_link_libraries(test
                      ${Boost_SYSTEM_LIBRARY}
                      ${Boost_PROGRAM_OPTIONS_LIBRARY}
                      ${Boost_IOSTREAMS_LIBRARY})
